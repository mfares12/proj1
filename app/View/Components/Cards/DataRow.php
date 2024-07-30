<?php

namespace App\View\Components\Cards;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class DataRow extends Component
{

    public $label;
    public $value;
    public $html;
    public $otherClasses;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($label, $value, $html = false, $otherClasses = '')
    {
        $this->label = $label;
        $this->value = $value;
        $this->html = $html;
        $this->otherClasses = $otherClasses;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return View|string
     */
    public function render()
    {
        return view('components.cards.data-row');
    }

}
