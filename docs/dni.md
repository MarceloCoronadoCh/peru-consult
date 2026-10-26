# DNI
Consulta de DNI.
> Fuente: **SUNAT** con *Fallback* automático a **eldni.com**.

El endpoint `registro/solicitante` de SUNAT exige el campo `token` no vacío (se genera uno aleatorio por consulta) y no responde para todos los DNIs; si no hay datos, la consulta automáticamente prueba con elDni.

## Ejemplo

```php
use Peru\Jne\DniFactory;

require 'vendor/autoload.php';

$dni = '46658592';

// La consulta SUNAT -> elDni en cascada (Fallback)
$factory = new DniFactory();
$cs = $factory->create();

$person = $cs->get($dni);
if (!$person) {
    echo 'Not found';
    return;
}

echo json_encode($person);

```

## Resultado

Resultado en formato json.

```json
{
  "dni": "46658592",
  "nombres": "LESLY LICET",
  "apellidoPaterno": "PEREZ",
  "apellidoMaterno": "PEÑA",
  "codVerifica": "6"
}
```
