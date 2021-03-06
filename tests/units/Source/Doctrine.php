<?php

namespace Polinome\Trieur\test\units\Source;

use atoum as Atoum;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Solire\Conf\Conf;
use Solire\Conf\Loader;
use Polinome\Trieur\Columns;

class Doctrine extends Atoum
{
    public $connection = null;

    private function getConnection()
    {
        if ($this->connection !== null) {
            return $this->connection;
        }

        $this->mockGenerator->shuntParentClassCalls();

        $this->mockGenerator->orphanize('__construct');
        $pdo = new \mock\PDO();

        $this->mockGenerator->orphanize('__construct');
        $this->connection = new \mock\Doctrine\DBAL\Connection();
        $this->connection->getMockController()->connect = function () {
        };
        $this->connection->getMockController()->quote = function ($input) {
            return '"' . addslashes($input) . '"';
        };

        $this->connection->getMockController()->getDatabasePlatform = function () {
            return new MySqlPlatform();
        };

        $this->mockGenerator->unshuntParentClassCalls();

        return $this->connection;
    }

    public function testConstruct01()
    {
        $connection = $this->getConnection();

        $conf = new Conf();
        $conf->select = [
            'a',
            'v',
        ];
        $conf->from = new Conf();
        $conf->from->name = 'tt';
        $conf->from->alias = 't';

        $columns = new Columns(new Conf());

        $this
            ->if($c = $this->newTestedInstance($conf, $columns, $connection))
                ->object($c)
                ->object($qB = $c->getQuery())
                    ->isInstanceOf('\Doctrine\DBAL\Query\QueryBuilder')

                ->string($qB->getSQL())
                    ->isEqualTo('SELECT a, v FROM tt t')

                ->object($qB = $c->getDataQuery())
                    ->isInstanceOf('\Doctrine\DBAL\Query\QueryBuilder')
                    ->string($qB->getSQL())
                    ->isEqualTo('SELECT a, v FROM tt t')

                ->object($qB = $c->getCountQuery())
                    ->isInstanceOf('\Doctrine\DBAL\Query\QueryBuilder')
                    ->string($qB->getSQL())
                    ->isEqualTo('SELECT COUNT(DISTINCT a, v) FROM tt t')

                ->object($qB = $c->getFilteredCountQuery())
                    ->isInstanceOf('\Doctrine\DBAL\Query\QueryBuilder')
                    ->string($qB->getSQL())
                    ->isEqualTo('SELECT COUNT(DISTINCT a, v) FROM tt t')
        ;
    }

    public function testConstruct02()
    {
        $connection = $this->getConnection();

        $conf = Loader::load([
            'select' => [
                'a',
                'v',
            ],
            'from' => [
                'name' => 'tt',
                'alias' => 't',
            ],
            'where' => [
                'a = v',
            ],
            'innerJoin' => [
                [
                    'name' => 'uu',
                    'alias' => 'u',
                    'on' => 'u.c = t.v',
                ],
            ],
        ]);

        $columns = new Columns(new Conf());

        $this
            ->if($c = $this->newTestedInstance($conf, $columns, $connection))
                ->object($c)
                ->object($qB = $c->getQuery())
                    ->isInstanceOf('\Doctrine\DBAL\Query\QueryBuilder')

                ->string($qB->getSQL())
                    ->isEqualTo('SELECT a, v FROM tt t INNER JOIN uu u ON u.c = t.v WHERE a = v')

                ->object($qB = $c->getDataQuery())
                    ->isInstanceOf('\Doctrine\DBAL\Query\QueryBuilder')
                    ->string($qB->getSQL())
                    ->isEqualTo('SELECT a, v FROM tt t INNER JOIN uu u ON u.c = t.v WHERE a = v')

                ->object($qB = $c->getCountQuery())
                    ->isInstanceOf('\Doctrine\DBAL\Query\QueryBuilder')
                    ->string($qB->getSQL())
                    ->isEqualTo('SELECT COUNT(DISTINCT a, v) FROM tt t INNER JOIN uu u ON u.c = t.v WHERE a = v')

                ->object($qB = $c->getFilteredCountQuery())
                    ->isInstanceOf('\Doctrine\DBAL\Query\QueryBuilder')
                    ->string($qB->getSQL())
                    ->isEqualTo('SELECT COUNT(DISTINCT a, v) FROM tt t INNER JOIN uu u ON u.c = t.v WHERE a = v')
        ;
    }
    public function testConstruct03()
    {
        $connection = $this->getConnection();

        $conf = Loader::load([
            'select' => [
                'a',
                'v',
            ],
            'from' => [
                'name' => 'tt',
                'alias' => 't',
            ],
            'where' => [
                'a = v',
            ],
            'innerJoin' => [
                [
                    'name' => 'uu',
                    'alias' => 'u',
                    'on' => 'u.c = t.v',
                ],
            ],
            'group' => 't.a',
        ]);

        $columns = new Columns(Loader::load([
            'a' => [
                'source' => 't.a',
            ],
        ]));

        $this
            ->if($c = $this->newTestedInstance($conf, $columns, $connection))
            ->and($c->addOrder('a', 'ASC'))
            ->and($c->addFilter([
                ['t.a'],
                'trieur php',
                'Contain',
            ]))
            ->and($c->setOffset(10))
            ->and($c->setLength(5))

            ->and($qB = $c->getQuery())
            ->string($qB->getSQL())
                ->isEqualTo('SELECT a, v FROM tt t INNER JOIN uu u ON u.c = t.v WHERE a = v')

            ->and($qB = $c->getDataQuery())
            ->string($qB->getSQL())
                ->isEqualTo(
                    'SELECT a, v '
                    . 'FROM tt t '
                    . 'INNER JOIN uu u ON u.c = t.v '
                    . 'WHERE (a = v) '
                    . 'AND (t.a LIKE "%trieur php%" OR t.a LIKE "%trieur%" OR t.a LIKE "%php%") '
                    . 'GROUP BY t.a '
                    . 'ORDER BY IF(t.a LIKE "%trieur php%", 10, 0) + IF(t.a LIKE "%trieur%", 6, 0) + IF(t.a LIKE "%php%", 3, 0) DESC, '
                    . 't.a '
                    . 'ASC '
                    . 'LIMIT 5 '
                    . 'OFFSET 10'
                )

            ->and($qB = $c->getCountQuery())
            ->string($qB->getSQL())
                ->isEqualTo(
                    'SELECT COUNT(DISTINCT t.a) '
                    . 'FROM tt t '
                    . 'INNER JOIN uu u ON u.c = t.v '
                    . 'WHERE a = v'
                )

            ->and($qB = $c->getFilteredCountQuery())
            ->string($qB->getSQL())
                ->isEqualTo(
                    'SELECT COUNT(DISTINCT t.a) '
                    . 'FROM tt t '
                    . 'INNER JOIN uu u '
                    . 'ON u.c = t.v '
                    . 'WHERE (a = v) '
                    . 'AND (t.a LIKE "%trieur php%" OR t.a LIKE "%trieur%" OR t.a LIKE "%php%")'
                )
        ;
    }
}
