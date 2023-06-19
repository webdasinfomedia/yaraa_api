<?php

namespace App\Traits;

use App\Scopes\ArchiveScope;

/**
 * 
 * @author Priyal Patel
 * 
 * @method static static|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder withArchived(bool $withTrashed = true)
 * @method static static|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder onlyArchived()
 * @method static static|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder withoutArchived()
 */
trait Archivable
{
    /**
     * Boot the archivable trait for a model.
     *
     * @return void
     */
    public static function bootArchivable()
    {
        static::addGlobalScope(new ArchiveScope);
    }

    /**
     * Archive a model instance.
     *
     * @return bool|null
     */
    public function archive()
    {
        $archivedBy =  $this->{$this->getArchivedByColumn()};

        if( ! is_array($archivedBy) || ! in_array(auth()->id(),$archivedBy)){
            return $this->push($this->getArchivedByColumn(),auth()->id());
        }

    }

     /**
     * Unarchive a model instance.
     *
     * @return bool|null
     */
    protected function UnArchive()
    {
        // $query = $this->newModelQuery()->where($this->getKeyName(), $this->getKey());        
        // $syncArchivedBy = $this->archived_by;
        // $key = array_search(auth()->id(),$syncArchivedBy);
        // unset($syncArchivedBy[$key]);
        // $columns = [
        //     'archived_by' => $syncArchivedBy,
        // ];
        // $result = $query->update($columns); // Works
        #############################
        // return $this->withoutGlobalScope(ArchiveScope::class)->pull($this->getArchivedByColumn(),auth()->id()); // works
        #############################

        $query = $this->setKeysForSaveQuery($this->newModelQuery());
        
        $syncArchivedBy = $this->{$this->getArchivedByColumn()};
        $key = array_search(auth()->id(),$syncArchivedBy);
        unset($syncArchivedBy[$key]);

        $columns = [$this->getArchivedByColumn() => $syncArchivedBy];

        $query->update($columns);

        $this->syncOriginalAttributes(array_keys($columns));

        $this->fireModelEvent('archived', false);
        
        return $query;
        
    }

    /**
     * Determine if the model instance has been archived.
     *
     * @return bool
     */
    public function archived()
    {
        return in_array(auth()->id(),$this->{$this->getArchivedByColumn()});
    }

    /**
     * Get the name of the "archived by" column.
     *
     * @return string
     */
    public function getArchivedByColumn()
    {
        return defined('static::ARCHIVED_BY') ? static::ARCHIVED_BY : 'archived_by';
    }

    /**
     * Get the fully qualified "archived by" column.
     *
     * @return string
     */
    public function getQualifiedArchivedByColumn()
    {
        return $this->qualifyColumn($this->getArchivedByColumn());
    }
}
