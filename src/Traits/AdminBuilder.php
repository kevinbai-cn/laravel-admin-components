<?php

namespace Kevinbai\Admin\Traits;

use Kevinbai\Admin\Form;
use Kevinbai\Admin\Grid;
use Kevinbai\Admin\Tree;

trait AdminBuilder
{
    /**
     * @param \Closure $callback
     *
     * @return Grid
     */
    public static function grid(\Closure $callback)
    {
        return new Grid(new static(), $callback);
    }

    /**
     * @param \Closure $callback
     *
     * @return Form
     */
    public static function form(\Closure $callback)
    {
        Form::registerBuiltinFields();

        return new Form(new static(), $callback);
    }

    /**
     * @param \Closure $callback
     *
     * @return Tree
     */
    public static function tree(\Closure $callback = null)
    {
        return new Tree(new static(), $callback);
    }
}
