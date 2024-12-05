<?php
class block_validador extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_validador');
    }

    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }

        global $COURSE;

        // Contenido del bloque
        $this->content = new stdClass();
        $this->content->text = get_string('message', 'block_validador', $COURSE->shortname);
        $this->content->footer = '';

        return $this->content;
    }
}