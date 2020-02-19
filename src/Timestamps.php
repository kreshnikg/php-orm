<?php

namespace Database;

trait Timestamps {
    /**
     * Field name for insertion timestamp
     * @var string
     */
    protected $CREATED_AT = 'created_at';

    /**
     * Field name for update timestamp
     * @var string
     */
    protected $UPDATED_AT = 'updated_at';
}
