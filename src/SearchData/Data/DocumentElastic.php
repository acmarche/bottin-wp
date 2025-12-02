<?php


namespace AcMarche\Bottin\SearchData\Data;


class DocumentElastic
{
    public string $id;
    public string $name;
    public ?string $excerpt = null;
    public string $content;
    public array $tags=[];
    public string $date;
    public string $url;
    public string $type;
    public int $count = 0;
    public array $ids=[];
}
