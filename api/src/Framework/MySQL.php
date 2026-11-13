<?php

declare(strict_types=1);

namespace ADS\TakeHome\Framework;

use PDO;
use PDOException;

/**
 * MySQL adatbáziskapcsolat.
 *
 * A kapcsolat csak az első lekérdezéskor épül fel. Így egy elérhetetlen
 * adatbázis hibája a kéréskezelésen belül keletkezik, és a Router JSON
 * hibaválaszként tudja visszaadni.
 */
class MySQL implements DatabaseInterface
{
    private PDO|null $conn = null;

    public function __construct(
        private readonly string $host,
        private readonly string $schema,
        private readonly string $username,
        private readonly string $password,
        private readonly int $port = 3306,
    ) {
    }

    /**
     * Végrehajtja a lekérdezést és az eredményt visszaadja asszociatív tömbök
     * listájaként.
     *
     * Valódi (szerveroldali) prepared statementet használ: az SQL szöveg és a
     * paraméterek külön jutnak el a MySQL szerverhez, így a paraméterek
     * értéke soha nem válhat az SQL utasítás részévé.
     *
     * Natív prepared statementnél egy név szerinti paraméter csak egyszer
     * szerepelhet a lekérdezésben.
     *
     * @param array<string, scalar|null> $params Behelyettesítendő paraméterek,
     * név szerint, pl. `[":cityId" => 1]`. Pozícionális paramétereket nem
     * fogad el!
     *
     * @return list<array<string, scalar|null>>
     *
     * @throws PDOException ha a kapcsolódás vagy a lekérdezés sikertelen.
     */
    public function queryAssoc(string $sql, array $params = []): array
    {
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $paramName => $paramValue) {
            $stmt->bindValue($paramName, $paramValue, $this->getParamType($paramValue));
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getConnection(): PDO
    {
        if ($this->conn === null) {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->schema};charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->username, $this->password, [
                // Lekérdezés hiba esetén PHP kivétel legyen.
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                // Valódi prepared statement a PDO által emulált helyett. Mellékhatás:
                // a számok PHP int / float típusként jönnek vissza, nem szövegként.
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }
        return $this->conn;
    }

    private function getParamType(bool|float|int|string|null $value): int
    {
        return match (true) {
            is_int($value) => PDO::PARAM_INT,
            is_bool($value) => PDO::PARAM_BOOL,
            is_null($value) => PDO::PARAM_NULL,
            // A float értéket szövegként küldjük, a MySQL alakítja át.
            default => PDO::PARAM_STR,
        };
    }
}
