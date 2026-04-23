<?php

namespace Drupal\pokedex\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Servicio para consumir la PokéAPI.
 */
class PokeApiService {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected ClientInterface $httpClient;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Base URL de la PokéAPI.
   */
  const API_BASE_URL = 'https://pokeapi.co/api/v2';

  /**
   * Constructor.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(ClientInterface $http_client, LoggerChannelFactoryInterface $logger_factory) {
    $this->httpClient = $http_client;
    $this->logger = $logger_factory->get('pokedex');
  }

  /**
   * Obtiene un listado de Pokémon con paginación.
   *
   * @param int $limit
   *   Número de resultados por página.
   * @param int $offset
   *   Offset para la paginación.
   * @param bool $with_types
   *   Si TRUE, obtiene los tipos de cada Pokémon (más lento).
   *
   * @return array
   *   Array con los datos de los Pokémon.
   *
   * @throws \Exception
   *   Si falla la petición a la API.
   */
  public function getPokemonList(int $limit, int $offset, bool $with_types = TRUE): array {
    $url = self::API_BASE_URL . "/pokemon?limit={$limit}&offset={$offset}";

    try {
      $response = $this->httpClient->request('GET', $url, [
        'timeout' => 10,
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);

      if (!isset($data['results']) || !is_array($data['results'])) {
        throw new \Exception('Formato de respuesta inválido');
      }

      // Extraer el ID de cada Pokémon desde la URL
      $pokemon_list = [];
      foreach ($data['results'] as $pokemon) {
        // Extraer ID desde URL: https://pokeapi.co/api/v2/pokemon/1/
        preg_match('/\/pokemon\/(\d+)\/$/', $pokemon['url'], $matches);
        $id = $matches[1] ?? 0;

        $pokemon_data = [
          'id' => (int) $id,
          'name' => $pokemon['name'],
          'types' => [],
        ];

        // Si se solicita, obtener los tipos de cada Pokémon
        if ($with_types) {
          try {
            $detail = $this->getPokemonDetail($pokemon['name']);
            $pokemon_data['types'] = $detail['types'] ?? [];
          }
          catch (\Exception $e) {
            // Si falla, continuar sin los tipos
            $this->logger->warning('No se pudieron obtener tipos para @name', [
              '@name' => $pokemon['name'],
            ]);
          }
        }

        $pokemon_list[] = $pokemon_data;
      }

      return [
        'count' => $data['count'] ?? 0,
        'results' => $pokemon_list,
      ];
    }
    catch (RequestException $e) {
      $this->logger->error('Error al consumir PokéAPI (listado): @message', [
        '@message' => $e->getMessage(),
      ]);
      throw new \Exception('Error al conectar con la API de Pokémon');
    }
  }

  /**
   * Obtiene la lista de tipos disponibles.
   *
   * @return array
   *   Array con nombres de tipos.
   *
   * @throws \Exception
   *   Si falla la petición a la API.
   */
  public function getPokemonTypes(): array {
    $url = self::API_BASE_URL . "/type";

    try {
      $response = $this->httpClient->request('GET', $url, [
        'timeout' => 10,
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);

      if (!isset($data['results']) || !is_array($data['results'])) {
        throw new \Exception('Formato de respuesta inválido');
      }

      $types = [];
      foreach ($data['results'] as $type) {
        // Excluir tipos especiales que no son de Pokémon regulares
        if (!in_array($type['name'], ['unknown', 'shadow'])) {
          $types[] = $type['name'];
        }
      }

      return $types;
    }
    catch (RequestException $e) {
      $this->logger->error('Error al obtener tipos: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Obtiene Pokémon filtrados por tipo con paginación.
   *
   * @param string $type
   *   Nombre del tipo (ej: 'fire', 'water').
   * @param int $limit
   *   Número de resultados por página.
   * @param int $offset
   *   Offset para la paginación.
   *
   * @return array
   *   Array con los datos de los Pokémon filtrados.
   *
   * @throws \Exception
   *   Si falla la petición a la API.
   */
  public function getPokemonByType(string $type, int $limit, int $offset): array {
    $url = self::API_BASE_URL . "/type/{$type}";

    try {
      $response = $this->httpClient->request('GET', $url, [
        'timeout' => 15,
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);

      if (!isset($data['pokemon']) || !is_array($data['pokemon'])) {
        throw new \Exception('Formato de respuesta inválido');
      }

      // Extraer solo los Pokémon (no las formas alternativas)
      $all_pokemon = [];
      foreach ($data['pokemon'] as $entry) {
        $pokemon = $entry['pokemon'];
        
        // Extraer ID desde URL
        preg_match('/\/pokemon\/(\d+)\/$/', $pokemon['url'], $matches);
        $id = (int) ($matches[1] ?? 0);
        
        // Solo incluir Pokémon regulares (IDs < 10000)
        if ($id > 0 && $id < 10000) {
          $all_pokemon[] = [
            'id' => $id,
            'name' => $pokemon['name'],
            'url' => $pokemon['url'],
          ];
        }
      }

      // Ordenar por ID
      usort($all_pokemon, function ($a, $b) {
        return $a['id'] <=> $b['id'];
      });

      // Aplicar paginación manualmente
      $total_count = count($all_pokemon);
      $paginated = array_slice($all_pokemon, $offset, $limit);

      // Obtener detalles de cada Pokémon en la página actual
      $pokemon_list = [];
      foreach ($paginated as $pokemon) {
        try {
          $detail = $this->getPokemonDetail($pokemon['name']);
          $pokemon_list[] = [
            'id' => $pokemon['id'],
            'name' => $pokemon['name'],
            'types' => $detail['types'] ?? [],
          ];
        }
        catch (\Exception $e) {
          // Si falla un detalle, continuar con el siguiente
          $this->logger->warning('No se pudieron obtener detalles para @name', [
            '@name' => $pokemon['name'],
          ]);
        }
      }

      return [
        'count' => $total_count,
        'results' => $pokemon_list,
      ];
    }
    catch (RequestException $e) {
      $this->logger->error('Error al obtener Pokémon por tipo: @message', [
        '@message' => $e->getMessage(),
      ]);
      throw new \Exception('Error al filtrar Pokémon por tipo');
    }
  }

  /**
   * Obtiene los detalles de un Pokémon específico.
   *
   * @param string $name
   *   Nombre o ID del Pokémon.
   *
   * @return array
   *   Array con los datos detallados del Pokémon.
   *
   * @throws \Exception
   *   Si falla la petición a la API.
   */
  public function getPokemonDetail(string $name): array {
    $url = self::API_BASE_URL . "/pokemon/{$name}";

    try {
      $response = $this->httpClient->request('GET', $url, [
        'timeout' => 10,
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);

      if (!isset($data['id'])) {
        throw new \Exception('Formato de respuesta inválido');
      }

      // Extraer tipos
      $types = [];
      if (isset($data['types']) && is_array($data['types'])) {
        foreach ($data['types'] as $type_data) {
          $types[] = $type_data['type']['name'] ?? '';
        }
      }

      return [
        'id' => $data['id'],
        'name' => $data['name'],
        'sprite' => $data['sprites']['front_default'] ?? '',
        'types' => $types,
        'height' => $data['height'] ?? 0,
        'weight' => $data['weight'] ?? 0,
      ];
    }
    catch (RequestException $e) {
      $this->logger->error('Error al consumir PokéAPI (detalle): @message', [
        '@message' => $e->getMessage(),
      ]);
      throw new \Exception('Error al obtener detalles del Pokémon');
    }
  }

}
