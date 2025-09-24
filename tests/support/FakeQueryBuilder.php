<?php

class FakeQueryBuilder
{
    public $history = [];
    public $selects = [];
    public $from = null;
    public $joins = [];
    public $wheres = [];
    public $orders = [];
    public $limit = null;
    private $resultQueue = [];

    public function pushResult($rows)
    {
        $this->resultQueue[] = $rows;
    }

    public function select($select)
    {
        $this->selects[] = $select;
        return $this;
    }

    public function from($from)
    {
        $this->from = $from;
        return $this;
    }

    public function join($table, $condition, $type = '')
    {
        $this->joins[] = [$table, $condition, $type];
        return $this;
    }

    public function where($condition, $value = null, $escape = null)
    {
        $this->wheres[] = [$condition, $value, $escape];
        return $this;
    }

    public function order_by($column, $direction = '')
    {
        $this->orders[] = [$column, strtoupper($direction ?: 'ASC')];
        return $this;
    }

    public function limit($limit, $offset = null)
    {
        $this->limit = [$limit, $offset];
        return $this;
    }

    public function escape($value)
    {
        if (is_numeric($value)) {
            return (string) $value;
        }
        return "'" . str_replace("'", "''", (string) $value) . "'";
    }

    public function escape_like_str($value)
    {
        $replacements = ["%" => "\\%", "_" => "\\_", "'" => "''"];
        return strtr((string) $value, $replacements);
    }

    public function get($table = null)
    {
        $rows = [];
        if (!empty($this->resultQueue)) {
            $rows = array_shift($this->resultQueue);
        }

        $this->history[] = [
            'selects' => $this->selects,
            'from' => $this->from,
            'joins' => $this->joins,
            'wheres' => $this->wheres,
            'orders' => $this->orders,
            'limit' => $this->limit,
            'args' => $table,
        ];

        $this->reset();

        return new class($rows)
        {
            private $rows;

            public function __construct($rows)
            {
                $this->rows = $rows;
            }

            public function result()
            {
                return $this->rows;
            }
        };
    }

    private function reset()
    {
        $this->selects = [];
        $this->from = null;
        $this->joins = [];
        $this->wheres = [];
        $this->orders = [];
        $this->limit = null;
    }
}
