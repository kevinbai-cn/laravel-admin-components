<?php

namespace Kevinbai\Admin\Form\Field;

use Kevinbai\Admin\Form\Field;

class Decimal extends Field
{
    protected static $js = [
        '/packages/admin/AdminLTE/plugins/input-mask/jquery.inputmask.bundle.min.js',
    ];

    public function render()
    {
        $this->script = "$('.{$this->getElementClass()}').inputmask('decimal', {
    rightAlign: true
  });";

        return parent::render();
    }
}
