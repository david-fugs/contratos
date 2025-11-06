<?php
require_once __DIR__ . '/config/config.php';

echo "<h2>Estructura de la tabla CONTRATOS:</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Default</th></tr>";

$result = $mysqli->query("DESCRIBE contratos");
while($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>Primer contrato de ejemplo:</h2>";
$result2 = $mysqli->query("SELECT * FROM contratos LIMIT 1");
if ($row2 = $result2->fetch_assoc()) {
    echo "<pre>";
    print_r($row2);
    echo "</pre>";
} else {
    echo "<p>No hay contratos en la base de datos</p>";
}
?>
