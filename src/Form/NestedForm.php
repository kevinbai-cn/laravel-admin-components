<?php

namespace Kevinbai\Admin\Form;

use Kevinbai\Admin\Admin;
use Kevinbai\Admin\Form;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class NestedForm
{
    const UPDATE_KEY_NAME_OLD = 'old';

    const UPDATE_KEY_NAME_NEW = 'new';

    const DEFAULT_KEY_NAME = '_key_';

    const REMOVE_FLAG_NAME = '_remove_';

    const REMOVE_FLAG_CLASS = 'fom-removed';

    /**
     * @var \Illuminate\Database\Eloquent\Relations\HasMany|string
     */
    protected $relation;

    /**
     * Fields in form.
     *
     * @var Collection
     */
    protected $fields;

    /**
     * Scripts of form.
     *
     * @var array
     */
    protected $scripts = [];

    /**
     * Original data for this field.
     *
     * @var array
     */
    protected $original = [];

    /**
     * Create a new NestedForm instance.
     *
     * @param $relation
     */
    public function __construct($relation)
    {
        $this->relation = $relation;

        $this->fields = new Collection();
    }

    /**
     * Set original values for fields.
     *
     * @param array  $data
     * @param string $relatedKeyName
     *
     * @return $this
     */
    public function setOriginal($data, $relatedKeyName)
    {
        if (empty($data)) {
            return $this;
        }

        foreach ($data as $value) {
            $this->original[$value[$relatedKeyName]] = $value;
        }

        return $this;
    }

    /**
     * Prepare for insert or update.
     *
     * @param array $input
     *
     * @return mixed
     */
    public function prepare($input)
    {
        $new = $old = [];

        foreach (array_get($input, static::UPDATE_KEY_NAME_NEW, []) as $record) {
            $new[] = $this->prepareRecord($record);
        }

        array_set($input, static::UPDATE_KEY_NAME_NEW, $new);

        foreach (array_get($input, static::UPDATE_KEY_NAME_OLD, []) as $key => $record) {
            $this->setFieldOriginalValue($key);
            $old[$key] = $this->prepareRecord($record);
        }

        array_set($input, static::UPDATE_KEY_NAME_OLD, $old);

        return $input;
    }

    /**
     * Set original data for each field.
     *
     * @param string $key
     *
     * @return void
     */
    protected function setFieldOriginalValue($key)
    {
        $values = $this->original[$key];

        $this->fields->each(function (Field $field) use ($values) {
            $field->setOriginal($values);
        });
    }

    /**
     * Do prepare work before store and update.
     *
     * @param array $record
     *
     * @return array
     */
    protected function prepareRecord($record)
    {
        if ($record[static::REMOVE_FLAG_NAME] == 1) {
            return $record;
        }

        $prepared = [];

        foreach ($this->fields as $field) {
            $columns = $field->column();

            $value = $this->fetchColumnValue($record, $columns);

            if (method_exists($field, 'prepare')) {
                $value = $field->prepare($value);
            }

            if ($value != $field->original()) {
                if (is_array($columns)) {
                    foreach ($columns as $name => $column) {
                        array_set($prepared, $column, $value[$name]);
                    }
                } elseif (is_string($columns)) {
                    array_set($prepared, $columns, $value);
                }
            }
        }

        return $prepared;
    }

    /**
     * Fetch value in input data by column name.
     *
     * @param array        $data
     * @param string|array $columns
     *
     * @return array|mixed
     */
    protected function fetchColumnValue($data, $columns)
    {
        if (is_string($columns)) {
            return array_get($data, $columns);
        }

        if (is_array($columns)) {
            $value = [];
            foreach ($columns as $name => $column) {
                if (!array_has($data, $column)) {
                    continue;
                }
                $value[$name] = array_get($data, $column);
            }

            return $value;
        }
    }

    /**
     * @param Field $field
     *
     * @return $this
     */
    public function pushField(Field $field)
    {
        $this->fields->push($field);

        return $this;
    }

    /**
     * Get fields of this form.
     *
     * @return Collection
     */
    public function fields()
    {
        return $this->fields;
    }

    /**
     * Get relation name of this form.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|string
     */
    public function getRelationName()
    {
        return $this->relation;
    }

    /**
     * Fill data to all fields in form.
     *
     * @param array $data
     *
     * @return $this
     */
    public function fill(array $data)
    {
        foreach ($this->fields() as $field) {
            $field->fill($data);
        }

        return $this;
    }

    /**
     * Set form element name for original records.
     *
     * @param string $key
     *
     * @return $this
     */
    public function setElementNameForOriginal($key)
    {
        return $this->setElementName(static::UPDATE_KEY_NAME_OLD, $key);
    }

    /**
     * Set form element name for added form elements.
     *
     * @param null $key
     *
     * @return $this
     */
    public function setElementNameForNew($key = null)
    {
        return $this->setElementName(static::UPDATE_KEY_NAME_NEW, $key);
    }

    /**
     * Set error key for each field in the nested form.
     *
     * @param string $parent
     * @param string $column
     * @param string $key
     *
     * @return $this
     */
    public function setErrorKey($parent, $type, $key)
    {
        foreach ($this->fields as $field) {
            $column = $field->column();

            $errorKey = '';

            if (is_array($column)) {
                foreach ($column as $k => $name) {
                    $errorKey[$k] = "$parent.$type.$key.$name";
                }
            } else {
                $errorKey = "$parent.$type.$key.{$field->column()}";
            }

            $field->setErrorKey($errorKey);
        }

        return $this;
    }

    /**
     * Set form element name.
     *
     * @param string $type
     * @param null   $key
     *
     * @return $this
     */
    protected function setElementName($type, $key = null)
    {
        $this->fields->each(function (Field $field) use ($type, $key) {
            $column = $field->column();

            if (is_array($column)) {
                $name = array_map(function ($col) use ($type, $key) {
                    return $this->formatElementName($type, $col, $key);
                }, $column);
            } else {
                $name = $this->formatElementName($type, $column, $key);
            }

            $field->setElementName($name);
        });

        return $this;
    }

    /**
     * Format form element name.
     *
     * @param string $type
     * @param string $column
     * @param string $key
     *
     * @return string
     */
    protected function formatElementName($type, $column, $key = null)
    {
        $key = is_null($key) ? static::DEFAULT_KEY_NAME : $key;

        return sprintf('%s[%s][%s][%s]', $this->relation, $type, $key, $column);
    }

    /**
     * Update relation data with input data.
     *
     * @param array $input
     */
    public function update(array $input)
    {
        $this->updateMany(array_get($input, static::UPDATE_KEY_NAME_OLD, []));

        $this->createMany(array_get($input, static::UPDATE_KEY_NAME_NEW, []));
    }

    /**
     * Update an array of new instances of the related model.
     *
     * @param array $old
     *
     * @return void
     */
    protected function updateMany(array $old)
    {
        if (empty($old)) {
            return;
        }

        $ids = $updates = [];
        foreach ($old as $pk => $value) {
            if ($value[static::REMOVE_FLAG_NAME] == 1) {
                $ids[] = $pk;
            } else {
                $updates[$pk] = $value;
            }
        }

        $this->performDestroyMany($ids);

        $this->performUpdateMany($updates);
    }

    /**
     * Perform destroy of many old records.
     *
     * @param array $removes
     *
     * @return void
     */
    protected function performDestroyMany(array $removes)
    {
        if (!empty($removes)) {
            $this->relation->getRelated()->destroy($removes);
        }
    }

    /**
     * Perform update of many old records.
     *
     * @param array $updates
     *
     * @return void
     */
    protected function performUpdateMany(array $updates)
    {
        if (empty($updates)) {
            return;
        }

        $this->relation->find(array_keys($updates))
            ->each(function (Model $model) use ($updates) {
                $update = $updates[$model->{$model->getKeyName()}];

                $update = array_map(function ($item) {
                    if (is_array($item)) {
                        $item = implode(',', $item);
                    }

                    return $item;
                }, $update);

                array_forget($update, static::REMOVE_FLAG_NAME);

                $model->update($update);
            });
    }

    /**
     * Create an array of new instances of the related model.
     *
     * @param array $input
     *
     * @return array
     */
    protected function createMany(array $input)
    {
        if (empty($input)) {
            return;
        }

        collect($input)->reject(function ($record) {
            return $record[static::REMOVE_FLAG_NAME] == 1;
        })->map(function ($record) {
            unset($record[static::REMOVE_FLAG_NAME]);

            return $record;
        })->reject(function ($record) {
            return empty(array_filter($record));
        })->map(function ($record) {
            return array_map(function ($item) {
                if (is_array($item)) {
                    $item = implode(',', $item);
                }

                return $item;
            }, $record);
        })->pipe(function ($records) {
            $this->relation->createMany($records->all());
        });
    }

    /**
     * Get form html without script.
     *
     * @return string
     */
    public function getFormHtml()
    {
        $html = '';

        foreach ($this->fields() as $field) {
            $html .= $field->render();

            if ($script = $field->getScript()) {
                $this->scripts[] = $field->getScript();

                array_pop(Admin::$script);
            }
        }

        return $html;
    }

    /**
     * Get form script as string.
     *
     * @return string
     */
    public function getFormScript()
    {
        return implode("\r\n", $this->scripts);
    }

    /**
     * Add nested-form fields dynamically.
     *
     * @param string $method
     * @param array  $arguments
     *
     * @return $this|Field
     */
    public function __call($method, $arguments)
    {
        if ($className = Form::findFieldClass($method)) {
            $column = array_get($arguments, 0, '');

            $element = new $className($column, array_slice($arguments, 1));

            $this->pushField($element);

            return $element;
        }

        return $this;
    }
}
