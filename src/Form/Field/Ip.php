<?php

namespace Kevinbai\Admin\Form\Field;

use Kevinbai\Admin\Form\Field;

class Ip extends Field
{
    protected $rules = 'ip';

    protected static $js = [
        '/packages/admin/AdminLTE/plugins/input-mask/jquery.inputmask.bundle.min.js',
    ];

    public function render()
    {
        $this->script = '$("[data-mask]").inputmask();';

        return parent::render();
    }
}
