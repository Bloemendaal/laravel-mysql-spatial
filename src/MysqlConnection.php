<?php

namespace Grimzy\LaravelMysqlSpatial;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Types\Type as DoctrineType;
use Doctrine\DBAL\Connection as DoctrineConnection;
use Doctrine\DBAL\Driver\PDOMySql\Driver;
use Grimzy\LaravelMysqlSpatial\Schema\Builder;
use Grimzy\LaravelMysqlSpatial\Schema\Grammars\MySqlGrammar;
use Illuminate\Database\MySqlConnection as IlluminateMySqlConnection;

class MysqlConnection extends IlluminateMySqlConnection
{
    public function __construct($pdo, $database = '', $tablePrefix = '', array $config = [])
    {
        parent::__construct($pdo, $database, $tablePrefix, $config);

        if (class_exists(DoctrineType::class)) {
            // Prevent geometry type fields from throwing a 'type not found' error when changing them
            $geometries = [
                'geometry',
                'point',
                'linestring',
                'polygon',
                'multipoint',
                'multilinestring',
                'multipolygon',
                'geometrycollection',
                'geomcollection',
            ];
            $dbPlatform = $this->getDoctrineConnection()->getDatabasePlatform();
            foreach ($geometries as $type) {
                $dbPlatform->registerDoctrineTypeMapping($type, 'string');
            }
        }
    }

    protected function getDoctrineConnection(): DoctrineConnection
    {
        return DriverManager::getConnection([
            'pdo' => $this->getPdo(),
            'dbname' => $this->getConfig('database'),
            'user' => $this->getConfig('username'),
            'password' => $this->getConfig('password'),
            'host' => $this->getConfig('host'),
            'port' => $this->getConfig('port'),
            'driver' => 'pdo_mysql',
        ], new Configuration());
    }

    protected function getDoctrineDriver(): \Doctrine\DBAL\Driver
    {
        $driverClass = $this->getDoctrineDriverClass();
        return new $driverClass;
    }

    protected function getDoctrineDriverClass(): string
    {
        return Driver::class;
    }

    protected function getDoctrineConfig(): array
    {
        return [
            'pdo' => $this->getPdo(),
            'dbname' => $this->getDatabaseName(),
            'user' => $this->getConfig('username'),
            'password' => $this->getConfig('password'),
            'host' => $this->getConfig('host'),
            'port' => $this->getConfig('port'),
            'driver' => 'pdo_mysql',
        ];
    }

    /**
     * Get the default schema grammar instance.
     *
     * @return \Illuminate\Database\Grammar
     */
    protected function getDefaultSchemaGrammar()
    {
        return $this->withTablePrefix(new MySqlGrammar());
    }

    /**
     * Get a schema builder instance for the connection.
     *
     * @return \Illuminate\Database\Schema\MySqlBuilder
     */
    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new Builder($this);
    }
}
