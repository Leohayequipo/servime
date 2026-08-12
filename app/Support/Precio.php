<?php

namespace App\Support;

/**
 * Criterio único para los precios del menú.
 *
 * Existía repartido en tres lados que no coincidían: `Comercio::formatearPrecioPromo()`,
 * el `parsePriceToCents()` del carrito y un `"$ " + valor` suelto en el JavaScript del
 * panel. Ese último rompía precios: el input mostraba "$5.700,00" como `570000` —le sacaba
 * la coma y los centavos quedaban pegados— y al perder el foco guardaba `$ 570000`. Bastaba
 * pasar por el campo, sin escribir nada, para multiplicar el precio por cien.
 */
class Precio
{
    /**
     * Deja el precio como se guarda en el menú.
     *
     * Solo interpreta cuando NO hay ambigüedad: un número pelado ("4500") es un precio sin
     * formatear y se formatea. Cualquier otra cosa ("$5.700,00", "12.50", "2x1") ya trae
     * una decisión de quien la escribió y se respeta tal cual — acá entran menús de
     * cualquier país y no podemos adivinar si el punto es miles o decimales.
     */
    public static function formatear(?string $precio): string
    {
        $precio = trim((string) $precio);

        if ($precio === '') {
            return '';
        }

        // Número pelado: el comercio escribió "4500" y le ponemos el formato.
        if (ctype_digit($precio)) {
            return '$'.number_format((float) $precio, 0, ',', '.');
        }

        // Ya trae separadores ("5.700,00"): respetamos su formato, pero le devolvemos el
        // "$" que el input le saca para editar. Sin esto, tocar un precio se lo comía.
        if (preg_match('/^[0-9.,\s]+$/', $precio)) {
            return '$'.$precio;
        }

        // Cualquier otra cosa ("2x1", "consultar", "$5.700,00") ya está como la quisieron.
        return $precio;
    }

    /**
     * El precio como debe verse en el input de edición: sin el símbolo, con su formato.
     *
     * NO se le quitan los separadores. Ese era el bug: `preg_replace('/[^0-9]/', ...)`
     * convertía "$5.700,00" en "570000".
     */
    public static function paraEditar(?string $precio): string
    {
        return trim(str_replace('$', '', (string) $precio));
    }
}
