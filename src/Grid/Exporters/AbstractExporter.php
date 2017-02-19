<?php

namespace Kevinbai\Admin\Grid\Exporters;

use Kevinbai\Admin\Grid;

abstract class AbstractExporter implements ExporterInterface
{
    /**
     * @var \Kevinbai\Admin\Grid
     */
    protected $grid;

    /**
     * Create a new exporter instance.
     *
     * @param $grid
     */
    public function __construct(Grid $grid)
    {
        $this->grid = $grid;
    }

    /**
     * Get table of grid.
     *
     * @return string
     */
    public function getTable()
    {
        return $this->grid->model()->eloquent()->getTable();
    }

    /**
     * Get data with export query.
     *
     * @return array
     */
    public function getData()
    {
        return $this->grid->getFilter()->execute();
    }

    /**
     * {@inheritdoc}
     */
    abstract public function export();
}
