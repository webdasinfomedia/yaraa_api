<?php

namespace App\Traits;

/**
 * 
 * @author Priyal Patel
 * 
 */
trait MongoArchivable
{
    use \App\Traits\Archivable;

    /**
     * @override method for mongodb support
     */
    public function getQualifiedArchivedByColumn()
    {
        return $this->getArchivedByColumn();
    }
}
