<?php
/**
 * Medium is a target for materializing
 * the data from an HTTP View instance.
 * max@nohex.com 20050817
 */

namespace Eix\Core\Responses\Http\Media;

abstract class Medium
{
    // The data.
    protected $data;
    // Placeholder for the final output.
    protected $document;
    // Title identifying the medium.
    protected $title;

    // Implement in this function the means to obtain a template.
    abstract protected function getTemplate();

    // Sets $this->data contents.
    public function setData($data)
    {
        $this->data = $data;
    }

    // Gets $this->data contents.
    public function getData()
    {
        return $this->data;
    }

    // Sets title of output result.
    public function setTitle($title)
    {
        $this->title = $title;
    }

    // Gets title of output result.
    public function getTitle()
    {
        return $this->title;
    }

    // Compiles the data and commits it to the medium.
    abstract protected function prepare();

    // Generates the final output.
    abstract public function render();
}
