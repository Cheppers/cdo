<?php

namespace Drupal\similarity_engine;

use Drupal\Core\Entity\EntityRepository;

/**
 * Class ApiConnector interacts with the Similarity Engine.
 */
class ApiConnector {
  /**
   * The guzzle client used for making requests to the endpoint.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The URL of the endpoint.
   *
   * @var string
   */
  protected $endpoint;

  /**
   * The Document Batch ID as identified by the Similarity Engine.
   *
   * @var string
   */
  protected $batchId;

  /**
   * ApiConnector constructor.
   */
  public function __construct() {
    $this->httpClient = \Drupal::httpClient();
    $this->endpoint = 'http://ec2-52-207-237-91.compute-1.amazonaws.com:5001';
    $this->batchId = '1234';
  }

  /**
   * Get's an array of nodes objects that are similar to the submitted text.
   *
   * @param string $text
   *   Concatenated title and body of a Petition node.
   *
   * @return \Drupal\node\Entity\Node[]
   *   Array of node entities.
   */
  protected function getSimilar($text) {
    $client = $this->httpClient;
    $options = [
      'text' => $text,
    ];
    $uri = $this->getUri('similar');
    $request = $client->post($uri, $options);
    $matches = $request->getBody();
    $entity_repository = new EntityRepository();
    $nodes = [];
    foreach ($matches as $match) {
      $nodes[$match] = $entity_repository->loadEntityByUuid('node', $match);
    }
    return $nodes;

  }

  /**
   * Adds a document to the Similarity Engine.
   *
   * @param string $text
   *   Concatenated title and body of a Petition Node Entity.
   * @param string $uuid
   *   The UUID of the node entity.
   *
   * @return int
   *   HTTP Response code.
   */
  protected function putDocument($text, $uuid) {
    $client = $this->httpClient;
    $uri = $this->getUri('documents', $uuid);
    $options = [
      'text' => $text,
    ];
    $request = $client->put($uri, $options);
    return $request->getStatusCode();
  }

  /**
   * Generates URI based on endpoint, batch ID, and any paths that are passed.
   *
   * @param string $service
   *   The service you are calling.
   * @param string $path
   *   Any additional paths after the batch_id.
   *
   * @return string
   *   URI for the service.
   */
  protected function getUri($service, $path = NULL) {
    return implode('/', [$this->endpoint, $service, $this->batchId, $path]);
  }

}
