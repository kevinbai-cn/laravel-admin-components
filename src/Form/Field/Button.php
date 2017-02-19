<?php

namespace Kevinbai\Admin\Form\Field;

use Kevinbai\Admin\Form\Field;

class Button extends Field
{
    protected $class = 'btn-primary';

    public function info()
    {
        $this->class = 'btn-info';

        return $this;
    }

    public function on($event, $callback)
    {
        $this->script = <<<EOT

        $('.{$this->getElementClass()}').on('$event', function() {
            $callback
        });

EOT;
    }
}
