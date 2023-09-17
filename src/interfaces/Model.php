<?php

namespace Lay\interfaces;

interface Model {
    public function add(array $columns) : array;
    public function edit(string|int $id, array $columns) : array;
    public function get(string|int $id) : array;
    public function list(int $index = 1, int $limit = 500) : array ;
    public function retire(string|int $id, string|int|null $act_by = null) : array;
}