<?php

namespace Drupal\ddo_mapado;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Link;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\Core\Url;

class MapadoUrlComputed extends FieldItemList {
  use ComputedItemListTrait;
  /**
   * Computed Mapado URL.
   */
  protected function computeValue() {
    $entity = $this->getEntity();
    if ($entity->isNew()) {
      return;
    }

    // Construction du lien vers la fiche Spectacle
    $entity_uri =
      Url::fromRoute('entity.node.canonical', ['node' => $entity->id()], ['absolute' => TRUE])
        ->toString();
    $link0 = array(
      'uri' => $entity_uri,
      'title' => "Réserver",
      'options' => array(),
    );

    // Construction du lien vers la billetterie
    $mapado_url = 'https://domainedo.mapado.com/event/';
    if ($entity->get('field_ticketing_url')->get(0)) {
      $mapado_url = $entity->get('field_ticketing_url')->get(0)->getUrl()->getUri();
    }
    $slug = $entity->field_mapado_apislug->value;
    $event_uri = $mapado_url . $slug;
    $link1 = array(
      'uri' => $event_uri,
      'title' => "Réserver",
      'options' => array(),
    );

    $this->list[0] = $this->createItem(0, $link0);
    $this->list[1] = $this->createItem(0, $link1);
  }
}
