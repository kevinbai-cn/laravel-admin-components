<?php

namespace Kevinbai\Admin;

use Closure;
use Kevinbai\Admin\Exception\Handle;
use Kevinbai\Admin\Grid\Column;
use Kevinbai\Admin\Grid\Displayers\Actions;
use Kevinbai\Admin\Grid\Displayers\RowSelector;
use Kevinbai\Admin\Grid\Exporter;
use Kevinbai\Admin\Grid\Filter;
use Kevinbai\Admin\Grid\Model;
use Kevinbai\Admin\Grid\Row;
use Kevinbai\Admin\Grid\Tools;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Schema;
use Jenssegers\Mongodb\Eloquent\Model as MongodbModel;

class Grid
{
    /**
     * The grid data model instance.
     *
     * @var \Kevinbai\Admin\Grid\Model
     */
    protected $model;

    /**
     * Collection of all grid columns.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $columns;

    /**
     * Collection of all data rows.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $rows;

    /**
     * Rows callable fucntion.
     *
     * @var \Closure
     */
    protected $rowsCallback;

    /**
     * All column names of the grid.
     *
     * @var array
     */
    public $columnNames = [];

    /**
     * Grid builder.
     *
     * @var \Closure
     */
    protected $builder;

    /**
     * Mark if the grid is builded.
     *
     * @var bool
     */
    protected $builded = false;

    /**
     * All variables in grid view.
     *
     * @var array
     */
    protected $variables = [];

    /**
     * The grid Filter.
     *
     * @var \Kevinbai\Admin\Grid\Filter
     */
    protected $filter;

    /**
     * Resource path of the grid.
     *
     * @var
     */
    protected $resourcePath;

    /**
     * Default primary key name.
     *
     * @var string
     */
    protected $keyName = 'id';

    /**
     * Allow batch deletion.
     *
     * @var bool
     */
    protected $allowBatchDeletion = true;

    /**
     * Allow creation.
     *
     * @var bool
     */
    protected $allowCreation = true;

    /**
     * Allow actions.
     *
     * @var bool
     */
    protected $allowActions = true;

    /**
     * Allow export data.
     *
     * @var bool
     */
    protected $allowExport = true;

    /**
     * If use grid filter.
     *
     * @var bool
     */
    protected $useFilter = true;

    /**
     * If grid use pagination.
     *
     * @var bool
     */
    protected $usePagination = true;

    /**
     * Export driver.
     *
     * @var string
     */
    protected $exporter;

    /**
     * View for grid to render.
     *
     * @var string
     */
    protected $view = 'admin::grid.table';

    /**
     * Per-page options.
     *
     * @var array
     */
    public $perPages = [10, 20, 30, 50, 100];

    /**
     * Default items count per-page.
     *
     * @var int
     */
    public $perPage = 20;

    /**
     * Header tools.
     *
     * @var Tools
     */
    public $tools;

    /**
     * Callback for grid actions.
     *
     * @var Closure
     */
    protected $actionsCallback;

    /**
     * Create a new grid instance.
     *
     * @param Eloquent $model
     * @param callable $builder
     */
    public function __construct(Eloquent $model, Closure $builder)
    {
        $this->keyName = $model->getKeyName();
        $this->model = new Model($model);
        $this->columns = new Collection();
        $this->rows = new Collection();
        $this->builder = $builder;

        $this->setupTools();
        $this->setupFilter();
        $this->setupExporter();
    }

    /**
     * Setup grid tools.
     */
    public function setupTools()
    {
        $this->tools = new Tools($this);
    }

    /**
     * Setup grid filter.
     *
     * @return void
     */
    protected function setupFilter()
    {
        $this->filter = new Filter($this, $this->model());
    }

    /**
     * Setup grid exporter.
     *
     * @return void
     */
    protected function setupExporter()
    {
        if (Input::has(Exporter::$queryName)) {
            $this->model()->usePaginate(false);

            call_user_func($this->builder, $this);

            (new Exporter($this))->resolve($this->exporter)->export();
        }
    }

    /**
     * Get primary key name of model.
     *
     * @return string
     */
    public function getKeyName()
    {
        return $this->keyName ?: 'id';
    }

    /**
     * Add column to Grid.
     *
     * @param string $name
     * @param string $label
     *
     * @return Column
     */
    public function column($name, $label = '')
    {
        $relationName = $relationColumn = '';

        if (strpos($name, '.') !== false) {
            list($relationName, $relationColumn) = explode('.', $name);

            $relation = $this->model()->eloquent()->$relationName();

            $label = empty($label) ? ucfirst($relationColumn) : $label;
        }

        $column = $this->addColumn($name, $label);

        if (isset($relation) && $relation instanceof Relation) {
            $this->model()->with($relationName);
            $column->setRelation($relationName, $relationColumn);
        }

        return $column;
    }

    /**
     * Batch add column to grid.
     *
     * @example
     * 1.$grid->columns(['name' => 'Name', 'email' => 'Email' ...]);
     * 2.$grid->columns('name', 'email' ...)
     *
     * @param array $columns
     *
     * @return Collection|void
     */
    public function columns($columns = [])
    {
        if (func_num_args() == 0) {
            return $this->columns;
        }

        if (func_num_args() == 1 && is_array($columns)) {
            foreach ($columns as $column => $label) {
                $this->column($column, $label);
            }

            return;
        }

        foreach (func_get_args() as $column) {
            $this->column($column);
        }
    }

    /**
     * Add column to grid.
     *
     * @param string $column
     * @param string $label
     *
     * @return Column
     */
    protected function addColumn($column = '', $label = '')
    {
        $column = new Column($column, $label);
        $column->setGrid($this);

        return $this->columns[] = $column;
    }

    /**
     * Get Grid model.
     *
     * @return Model
     */
    public function model()
    {
        return $this->model;
    }

    /**
     * Paginate the grid.
     *
     * @param int $perPage
     *
     * @return void
     */
    public function paginate($perPage = 20)
    {
        $this->perPage = $perPage;

        $this->model()->paginate($perPage);
    }

    /**
     * Get the grid paginator.
     *
     * @return mixed
     */
    public function paginator()
    {
        return new Tools\Paginator($this);
    }

    /**
     * Disable grid pagination.
     *
     * @return $this
     */
    public function disablePagination()
    {
        $this->model->usePaginate(false);

        $this->usePagination = false;

        return $this;
    }

    /**
     * If this grid use pagination.
     *
     * @return bool
     */
    public function usePagination()
    {
        return $this->usePagination;
    }

    /**
     * Set per-page options.
     *
     * @param array $perPages
     */
    public function perPages(array $perPages)
    {
        $this->perPages = $perPages;
    }

    /**
     * Disable all actions.
     *
     * @return $this
     */
    public function disableActions()
    {
        $this->allowActions = false;

        return $this;
    }

    /**
     * Set grid action callback.
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function actions(Closure $callback)
    {
        $this->actionsCallback = $callback;

        return $this;
    }

    /**
     * Add `actions` column for grid.
     *
     * @return void
     */
    protected function appendActionsColumn()
    {
        if (!$this->allowActions) {
            return;
        }

        $grid = $this;
        $callback = $this->actionsCallback;
        $column = $this->addColumn('__actions__', trans('admin::lang.action'));

        $column->display(function ($value) use ($grid, $column, $callback) {
            $actions = new Actions($value, $grid, $column, $this);

            return $actions->display($callback);
        });
    }

    /**
     * Prepend checkbox column for grid.
     *
     * @return void
     */
    protected function prependRowSelectorColumn()
    {
        $grid = $this;

        $column = new Column('__row_selector__', ' ');
        $column->setGrid($this);

        $column->display(function ($value) use ($grid, $column) {
            $actions = new RowSelector($value, $grid, $column, $this);

            return $actions->display();
        });

        $this->columns->prepend($column);
    }

    /**
     * Build the grid.
     *
     * @return void
     */
    public function build()
    {
        if ($this->builded) {
            return;
        }

        $data = $this->processFilter();

        $this->prependRowSelectorColumn();
        $this->appendActionsColumn();

        Column::setOriginalGridData($data);

        $this->columns->map(function (Column $column) use (&$data) {
            $data = $column->fill($data);

            $this->columnNames[] = $column->getName();
        });

        $this->buildRows($data);

        $this->builded = true;
    }

    /**
     * Disable grid filter.
     *
     * @return $this
     */
    public function disableFilter()
    {
        $this->useFilter = false;

        return $this;
    }

    /**
     * Get filter of Grid.
     *
     * @return Filter
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * Process the grid filter.
     *
     * @return array
     */
    public function processFilter()
    {
        call_user_func($this->builder, $this);

        return $this->filter->execute();
    }

    /**
     * Set the grid filter.
     *
     * @param Closure $callback
     */
    public function filter(Closure $callback)
    {
        call_user_func($callback, $this->filter);
    }

    /**
     * Render the grid filter.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function renderFilter()
    {
        if (!$this->useFilter) {
            return '';
        }

        return $this->filter->render();
    }

    /**
     * Build the grid rows.
     *
     * @param array $data
     *
     * @return void
     */
    protected function buildRows(array $data)
    {
        $this->rows = collect($data)->map(function ($val, $key) {
            $row = new Row($key, $val);

            $row->setKeyName($this->keyName);
            $row->setPath($this->resource());

            return $row;
        });

        if ($this->rowsCallback) {
            $this->rows->map($this->rowsCallback);
        }
    }

    /**
     * Set grid row callback function.
     *
     * @param callable $callable
     *
     * @return Collection|void
     */
    public function rows(Closure $callable = null)
    {
        if (is_null($callable)) {
            return $this->rows;
        }

        $this->rowsCallback = $callable;
    }

    /**
     * Setup grid tools.
     *
     * @param callable $callback
     *
     * @return void
     */
    public function tools(Closure $callback)
    {
        call_user_func($callback, $this->tools);
    }

    /**
     * Render custom tools.
     *
     * @return string
     */
    public function renderHeaderTools()
    {
        return $this->tools->render();
    }

    /**
     * Set exporter driver for Grid to export.
     *
     * @param $exporter
     *
     * @return $this
     */
    public function exporter($exporter)
    {
        $this->exporter = $exporter;

        return $this;
    }

    /**
     * Export url.
     *
     * @return string
     */
    public function exportUrl()
    {
        $input = Input::all();

        $input = array_merge($input, [Exporter::$queryName => true]);

        return $this->resource().'?'.http_build_query($input);
    }

    /**
     * If grid allows export.s.
     *
     * @return bool
     */
    public function allowExport()
    {
        return $this->allowExport;
    }

    /**
     * Disable export.
     *
     * @return $this
     */
    public function disableExport()
    {
        $this->allowExport = false;

        return $this;
    }

    /**
     * Render export button.
     *
     * @return Tools\ExportButton
     */
    public function renderExportButton()
    {
        return new Tools\ExportButton($this);
    }

    /**
     * If allow batch delete.
     *
     * @return bool
     */
    public function allowBatchDeletion()
    {
        return $this->allowBatchDeletion;
    }

    /**
     * Disable batch deletion.
     *
     * @return $this
     *
     * @deprecated
     */
    public function disableBatchDeletion()
    {
        $this->allowBatchDeletion = false;

        return $this;
    }

    /**
     * Disable creation.
     *
     * @return $this
     */
    public function disableCreation()
    {
        $this->allowCreation = false;

        return $this;
    }

    /**
     * If allow creation.
     *
     * @return bool
     */
    public function allowCreation()
    {
        return $this->allowCreation;
    }

    /**
     * Render create button for grid.
     *
     * @return Tools\CreateButton
     */
    public function renderCreateButton()
    {
        return new Tools\CreateButton($this);
    }

    /**
     * Get current resource uri.
     *
     * @param string $path
     *
     * @return string
     */
    public function resource($path = null)
    {
        if (!empty($path)) {
            $this->resourcePath = $path;

            return $this;
        }

        if (!empty($this->resourcePath)) {
            return $this->resourcePath;
        }

        return app('router')->current()->getPath();
    }

    /**
     * Handle table column for grid.
     *
     * @param string $method
     * @param string $label
     *
     * @return bool|Column
     */
    protected function handleTableColumn($method, $label)
    {
        $connection = $this->model()->eloquent()->getConnectionName();

        if (Schema::connection($connection)->hasColumn($this->model()->getTable(), $method)) {
            return $this->addColumn($method, $label);
        }

        return false;
    }

    /**
     * Handle get mutator column for grid.
     *
     * @param string $method
     * @param string $label
     *
     * @return bool|Column
     */
    protected function handleGetMutatorColumn($method, $label)
    {
        if ($this->model()->eloquent()->hasGetMutator($method)) {
            return $this->addColumn($method, $label);
        }

        return false;
    }

    /**
     * Handle relation column for grid.
     *
     * @param string $method
     * @param string $label
     *
     * @return bool|Column
     */
    protected function handleRelationColumn($method, $label)
    {
        $model = $this->model()->eloquent();

        if (!method_exists($model, $method)) {
            return false;
        }

        if (!($relation = $model->$method()) instanceof Relation) {
            return false;
        }

        if ($relation instanceof HasOne || $relation instanceof BelongsTo) {
            $this->model()->with($method);

            return $this->addColumn($method, $label)->setRelation($method);
        }

        if ($relation instanceof HasMany || $relation instanceof BelongsToMany || $relation instanceof MorphToMany) {
            $this->model()->with($method);

            return $this->addColumn($method, $label);
        }

        return false;
    }

    /**
     * Dynamically add columns to the grid view.
     *
     * @param $method
     * @param $arguments
     *
     * @return $this|Column
     */
    public function __call($method, $arguments)
    {
        $label = isset($arguments[0]) ? $arguments[0] : ucfirst($method);

        if ($this->model()->eloquent() instanceof MongodbModel) {
            return $this->addColumn($method, $label);
        }

        if ($column = $this->handleTableColumn($method, $label)) {
            return $column;
        }

        if ($column = $this->handleGetMutatorColumn($method, $label)) {
            return $column;
        }

        if ($column = $this->handleRelationColumn($method, $label)) {
            return $column;
        }

        return $this->addColumn($method, $label);
    }

    /**
     * Register column displayers.
     *
     * @return void.
     */
    public static function registerColumnDisplayer()
    {
        $map = [
            'editable'      => \Kevinbai\Admin\Grid\Displayers\Editable::class,
            'switch'        => \Kevinbai\Admin\Grid\Displayers\SwitchDisplay::class,
            'switchGroup'   => \Kevinbai\Admin\Grid\Displayers\SwitchGroup::class,
            'select'        => \Kevinbai\Admin\Grid\Displayers\Select::class,
            'image'         => \Kevinbai\Admin\Grid\Displayers\Image::class,
            'label'         => \Kevinbai\Admin\Grid\Displayers\Label::class,
            'button'        => \Kevinbai\Admin\Grid\Displayers\Button::class,
            'link'          => \Kevinbai\Admin\Grid\Displayers\Link::class,
            'badge'         => \Kevinbai\Admin\Grid\Displayers\Badge::class,
            'progressBar'   => \Kevinbai\Admin\Grid\Displayers\ProgressBar::class,
            'radio'         => \Kevinbai\Admin\Grid\Displayers\Radio::class,
            'checkbox'      => \Kevinbai\Admin\Grid\Displayers\Checkbox::class,
            'orderable'     => \Kevinbai\Admin\Grid\Displayers\Orderable::class,
        ];

        foreach ($map as $abstract => $class) {
            Column::extend($abstract, $class);
        }
    }

    /**
     * Add variables to grid view.
     *
     * @param array $variables
     *
     * @return $this
     */
    public function with($variables = [])
    {
        $this->variables = $variables;

        return $this;
    }

    /**
     * Get all variables will used in grid view.
     *
     * @return array
     */
    protected function variables()
    {
        $this->variables['grid'] = $this;

        return $this->variables;
    }

    /**
     * Set a view to render.
     *
     * @param string $view
     * @param array  $variables
     */
    public function view($view, $variables = [])
    {
        if (!empty($variables)) {
            $this->with($variables);
        }

        $this->view = $view;
    }

    /**
     * Get the string contents of the grid view.
     *
     * @return string
     */
    public function render()
    {
        try {
            $this->build();
        } catch (\Exception $e) {
            return with(new Handle($e))->render();
        }

        return view($this->view, $this->variables())->render();
    }

    /**
     * Get the string contents of the grid view.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }
}
