<?php

namespace Rayak;

use Goutte\Client;

/**
 * @author Ayyoub
 */
class PatentsScraper {

    private $client;
    private $config = array();
    private static $patentScraper = NULL;

    private function __construct(Client $client, array $config = null, $path) {
        $this->client = $client;
        $this->config = $config;
        $this->createPatentsFiles($path);
    }

    static function createPatentScraper(Client $client, array $config = null, $path) {
        self::$patentScraper = new PatentsScraper($client, $config, $path);
        return self::$patentScraper;
    }

    private function getFullPageLink($pageNumber) {
        $website = isset($this->config['website']) ? $this->config['website'] : '';
        $searchPage = isset($this->config['searchPage']) ? $this->config['searchPage'] : '';
        $queryParam = isset($this->config['queryParam']) ? $this->config['queryParam'] : '';
        $pageParam = isset($this->config['pageParam']) ? $this->config['pageParam'] : '';
        $query = isset($this->config['query']) ? $this->config['query'] : '';

        $fullPageLink = $website . $searchPage . '&' . $queryParam . '=' . $query . '&' . $pageParam . '=' . $pageNumber;

        return $fullPageLink;
    }

    private function getPatentLink($patent) {
        $website = isset($this->config['website']) ? $this->config['website'] : '';

        $link = $patent->getAttribute('href');
        $patentLink = $website . $link;
        return $patentLink;
    }

    private function createPatentFile($filePath, $patentContent) {
        $file = fopen($filePath, 'w');
        fputs($file, $patentContent);
        fclose($file);
    }

    private function createPatentsFiles($path) {
        $patentLinkFilter = isset($this->config['patentLinkFilter']) ? $this->config['patentLinkFilter'] : '';
        $patentContentFilter = isset($this->config['patentContentFilter']) ? $this->config['patentContentFilter'] : '';

        $nbPatents = 1;

        for ($page = 1;; $page++) {

            $fullPageLink = $this->getFullPageLink($page);

            $patentsCrawler = $this->client->request('GET', $fullPageLink);
            $patents = $patentsCrawler->filter($patentLinkFilter);

            if ($patents->count() == 0) {
                echo "\nDone !";
                break;
            }

            foreach ($patents as $patent) {
                $patentLink = $this->getPatentLink($patent);
                $crawler = $this->client->request('GET', $patentLink);

                $patentCrawler = $crawler->filter($patentContentFilter);
                $patentContent = $patentCrawler->html();

                $filePath = $path . '/' . $nbPatents . '.html';
                $this->createPatentFile($filePath, $patentContent);
                echo "$nbPatents.html Created.\n";
                $nbPatents++;
            }
        }
    }

}
