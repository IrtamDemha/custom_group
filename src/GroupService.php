<?php

namespace Drupal\custom_group;

use Drupal;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\group\Entity\Group;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class GroupService.
 */
class GroupService implements GroupServiceInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Drupal\Core\Routing\CurrentRouteMatch definition.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected CurrentRouteMatch $currentRouteMatch;

  /**
   * DruSymfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * The group storage class.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $groupStorage;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected CacheBackendInterface $cacheBackend;

  /**
   * Constructs a new GroupService object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, CurrentRouteMatch $current_route_match, RequestStack $request_stack, ConfigFactoryInterface $config_factory, CacheBackendInterface $cacheBackend) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentRouteMatch = $current_route_match;
    $this->cacheBackend = $cacheBackend;
    $this->requestStack = $request_stack;
    $this->configFactory = $config_factory;
    $this->groupStorage = $this->entityTypeManager->getStorage('group');
    $this->groupRelationshipStorage = $this->entityTypeManager->getStorage('group_relationship');
  }


  /**
   * Get the current active group from hostname
   *
   * @param bool $reset
   *
   * @return \Drupal\group\Entity\Group|null
   */
  public function getActiveGroup(bool $reset = FALSE): Group|null {
    static $drupal_static_fast;
    if (!isset($drupal_static_fast)) {
      $drupal_static_fast['active_group'] = &drupal_static(__FUNCTION__);
    }
    $group = &$drupal_static_fast['active_group'];
    if (empty($group) || $reset) {
      //      Get current hostname
      $hostname = (Drupal::request()->getHost());
      $cid = 'group-host-' . $hostname;
      //      Load group from cache
      if (($cache = $this->cacheBackend->get($cid)) && !$reset) {
        $group = $cache->data;
      }
      else {
        $gid = NULL;
        $config = $this->configFactory->get('domain_site_settings.domainconfigsettings');

        foreach ($config->getRawData() as $data) {
          if ($data['hostname'] == $hostname) {
            $gid = $data['gid'];
            break;
          }
        }
        if ($gid) {
          $group = $this->groupStorage->load($gid);
          if (!empty($group)) {
            //            Set cache group
            $this->cacheBackend->set($cid, $group, -1, [
              'group_list',
            ]);
          }
        }
      }
    }

    return $group;
  }

  /**
   * Get the current group  hostname from gid
   *
   * @param $gid
   * @param boolean $reset
   *
   * @return String
   */
  public function getGroupDomain($gid, bool $reset = FALSE): string {
    static $drupal_static_fast;
    if (!isset($drupal_static_fast)) {
      $drupal_static_fast['domain_group'] = &drupal_static(__FUNCTION__);
    }
    $domain = &$drupal_static_fast['domain_group'];
    if (empty($domain) || $reset) {
      $cid = 'group-domain-' . $gid;
      //      Load domain from cache
      if (($cache = $this->cacheBackend->get($cid)) && !$reset) {
        $domain = $cache->data;
      }
      else {
        $config = $this->configFactory->get('domain_site_settings.domainconfigsettings');
        foreach ($config->getRawData() as $data) {
          if ($data['gid'] == $gid) {
            $domain = $data['hostname'];
            break;
          }
        }
        if ($domain) {
          //            Set cache domain
          $this->cacheBackend->set($cid, $domain, -1, [
            'group_list',
          ]);
        }
      }
    }
    return ($domain);
  }

  /**
   * @param \Drupal\node\Entity\Node $node
   * @param bool $reset
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getRelatedGroups(Node $node, bool $reset = FALSE): array {
    $group = [];
    static $drupal_static_fast;
    if (!isset($drupal_static_fast)) {
      $drupal_static_fast['related_group'] = &drupal_static(__FUNCTION__);
    }
    $related_group = &$drupal_static_fast['related_group'];
    if (empty($related_group) || $reset) {
      $cid = 'group-related-' . $node->id();
      if (($cache = $this->cacheBackend->get($cid)) && !$reset) {
        $group = $cache->data;
      }
      else {
        $group_relationships = $this->groupRelationshipStorage->loadByProperties(['entity_id' => $node->id()]);
        if ($group_relationships) {
          foreach ($group_relationships as $relationship) {
            $group[] = $relationship->getGroup();
          }
          $this->cacheBackend->set($cid, $group, -1, [
            'group_list',
          ]);
        }
      }

    }
    return ($group);
  }

  /**
   * Get first related group label
   *
   * @param \Drupal\node\Entity\Node $node
   *
   * @return null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getFirstRelatedGroupLabel(Node $node) {
    if (!$groups = $this->getRelatedGroups($node)) {
      return NULL;
    }
    return (reset($groups)->label());
  }

}
