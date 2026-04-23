<?php

namespace Drupal\pokedex\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\pokedex\Service\PokeApiService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Controller para las rutas de la Pokédex.
 */
class PokedexController extends ControllerBase {

  /**
   * The PokeAPI service.
   *
   * @var \Drupal\pokedex\Service\PokeApiService
   */
  protected PokeApiService $pokeApiService;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * Constructor.
   *
   * @param \Drupal\pokedex\Service\PokeApiService $poke_api_service
   *   The PokeAPI service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(PokeApiService $poke_api_service, RequestStack $request_stack) {
    $this->pokeApiService = $poke_api_service;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('pokedex.pokeapi'),
      $container->get('request_stack')
    );
  }

  /**
   * Muestra el listado paginado de Pokémon.
   *
   * @return array|RedirectResponse
   *   Render array o redirección.
   */
  public function list() {
    $request = $this->requestStack->getCurrentRequest();
    $search = trim($request->query->get('search', ''));
    $type_filter = trim($request->query->get('type', ''));
    $page = (int) $request->query->get('page', 1);
    
    // Si hay búsqueda, intentar buscar el Pokémon
    if (!empty($search)) {
      try {
        // Intentar obtener el Pokémon (el servicio acepta nombre o ID)
        $pokemon = $this->pokeApiService->getPokemonDetail(strtolower($search));
        
        // Si existe, redirigir al detalle
        $url = Url::fromRoute('pokedex.detail', [
          'pokemon' => $pokemon['name'],
        ], [
          'query' => ['page' => $page],
        ]);
        return new RedirectResponse($url->toString());
      }
      catch (\Exception $e) {
        // Si no existe, mostrar estado "not found"
        return [
          '#theme' => 'pokedex_list',
          '#status' => 'not_found',
          '#items' => [],
          '#page' => $page,
          '#has_previous' => FALSE,
          '#has_next' => FALSE,
          '#error_message' => '',
          '#total_count' => 0,
          '#search_term' => $search,
          '#type_filter' => $type_filter,
          '#available_types' => [],
          '#cache' => [
            'max-age' => 0,
          ],
        ];
      }
    }
    
    // Validar que la página sea al menos 1
    if ($page < 1) {
      $page = 1;
    }

    $limit = 18;
    $offset = ($page - 1) * $limit;

    $status = 'success';
    $items = [];
    $total_count = 0;
    $error_message = '';
    $available_types = [];

    try {
      // Obtener lista de tipos disponibles
      $available_types = $this->pokeApiService->getPokemonTypes();
      
      // Si hay filtro de tipo, usar endpoint específico
      if (!empty($type_filter)) {
        $data = $this->pokeApiService->getPokemonByType($type_filter, $limit, $offset);
      }
      else {
        // Si no hay filtro, usar listado normal
        $data = $this->pokeApiService->getPokemonList($limit, $offset);
      }
      
      $items = $data['results'] ?? [];
      $total_count = $data['count'] ?? 0;
      
      // Si no hay resultados
      if (empty($items)) {
        $status = 'empty';
      }
    }
    catch (\Exception $e) {
      $status = 'error';
      $error_message = $e->getMessage();
      \Drupal::logger('pokedex')->error('Error en listado: @message', ['@message' => $e->getMessage()]);
    }

    // Calcular información de paginación
    $total_pages = $total_count > 0 ? (int) ceil($total_count / $limit) : 0;
    $has_previous = $page > 1;
    $has_next = $page < $total_pages;

    return [
      '#theme' => 'pokedex_list',
      '#status' => $status,
      '#items' => $items,
      '#page' => $page,
      '#has_previous' => $has_previous,
      '#has_next' => $has_next,
      '#error_message' => $error_message,
      '#total_count' => $total_count,
      '#search_term' => '',
      '#type_filter' => $type_filter,
      '#available_types' => $available_types,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Muestra el detalle de un Pokémon específico.
   *
   * @param string $pokemon
   *   Nombre o ID del Pokémon.
   *
   * @return array
   *   Render array.
   */
  public function detail(string $pokemon): array {
    $request = $this->requestStack->getCurrentRequest();
    $page = (int) $request->query->get('page', 1);
    
    $status = 'success';
    $data = [];
    $error_message = '';

    try {
      $data = $this->pokeApiService->getPokemonDetail($pokemon);
    }
    catch (\Exception $e) {
      $status = 'error';
      $error_message = $e->getMessage();
    }

    return [
      '#theme' => 'pokedex_detail',
      '#status' => $status,
      '#pokemon' => $data,
      '#error_message' => $error_message,
      '#page' => $page,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

}
