<?php
$conn = mysqli_connect('localhost', 'root', '', 'laravel');
$result = mysqli_query($conn, "SHOW COLUMNS FROM types_comptes WHERE Field LIKE '%renouvellement%'");
if ($result) {
    while($row = mysqli_fetch_assoc($result)) { 
        echo $row['Field'] . ' - ' . $row['Type'] . PHP_EOL;
    }
    if (mysqli_num_rows($result) == 0) {
        echo "Aucune colonne trouvée pour renouvellement" . PHP_EOL;
    }
} else {
    echo "Erreur: " . mysqli_error($conn) . PHP_EOL;
}
mysqli_close($conn);
