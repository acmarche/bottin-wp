<?php

namespace AcMarche\Bottin\Elasticsearch;

use Elastica\Mapping;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * https://elasticsearch-cheatsheet.jolicode.com/
 * Class ElasticServer
 * @package AcMarche\Elasticsearch
 */
class ElasticServer
{
    use ElasticClientTrait;

    const INDEX_NAME_MARCHE_BE = 'marchebe2';

    public function __construct()
    {
        $this->connect();
    }

    public function createIndex()
    {
        try {
            $analyser = Yaml::parse(file_get_contents(__DIR__.'/mappings/analyzers.yaml'));
            $settings = Yaml::parse(file_get_contents(__DIR__.'/mappings/settings.yaml'));
        } catch (ParseException $e) {
            printf('Unable to parse the YAML string: %s', $e->getMessage());

            return;
        }

        $settings['settings']['analysis'] = $analyser;
        $response                         = $this->index->create($settings, true);
        dump($response);
    }

    public function setMapping()
    {
        try {
            $properties = Yaml::parse(file_get_contents(__DIR__.'/mappings/mapping.yaml'));
            $mapping    = new Mapping($properties['mappings']['properties']);
            $response   = $this->index->setMapping($mapping);
            dump($response);
        } catch (ParseException $e) {
            printf('Unable to parse the YAML string: %s', $e->getMessage());
        }
    }
}
