<?php

namespace Drupal\Tests\layout_builder\Functional;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;

/**
 * @todo.
 *
 * @group layout_builder
 */
class LayoutSectionTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['layout_builder', 'field_ui', 'node', 'block_test'];

  /**
   * The name of the layout section field.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->createContentType([
      'type' => 'bundle_with_section_field',
    ]);
    $this->createContentType([
      'type' => 'bundle_without_section_field',
    ]);

    $this->fieldName = 'field_my_sections';

    $field_storage = FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => 'node',
      'type' => 'layout_section',
      'cardinality' => FieldStorageConfig::CARDINALITY_UNLIMITED,
    ]);
    $field_storage->setTranslatable(TRUE);
    $field_storage->save();

    $instance = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'bundle_with_section_field',
      'label' => 'My Sections',
    ]);
    $instance->save();

    $display = EntityViewDisplay::load('node.bundle_with_section_field.default');
    $display->setComponent($this->fieldName, [
      'type' => 'layout_section',
      'settings' => [],
    ]);
    $display->save();
    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
    ], 'foobar'));
  }

  /**
   */
  public function providerTestLayoutSectionFormatter() {
    $data = [];
    $data['block_with_context'] = [
      [
        [
          'layout' => 'layout_onecol',
          'section' => [
            'content' => [
              'baz' => [
                'plugin_id' => 'test_context_aware',
                'context_mapping' => [
                  'user' => '@user.current_user_context:current_user',
                ],
              ],
            ],
          ],
        ],
      ],
      [
        '.layout--onecol',
        '#test_context_aware--username',
      ],
      [
        'foobar',
        'User context found',
      ],
      'user',
      'user:2',
      'UNCACHEABLE',
    ];
    $data['single_section_single_block'] = [
      [
        [
          'layout' => 'layout_onecol',
          'section' => [
            'content' => [
              'baz' => [
                'plugin_id' => 'system_powered_by_block',
              ],
            ],
          ],
        ],
      ],
      '.layout--onecol',
      'Powered by',
      '',
      '',
      'MISS',
    ];
    $data['multiple_sections'] = [
      [
        [
          'layout' => 'layout_onecol',
          'section' => [
            'content' => [
              'baz' => [
                'plugin_id' => 'system_powered_by_block',
              ],
            ],
          ],
        ],
        [
          'layout' => 'layout_twocol',
          'section' => [
            'left' => [
              'foo' => [
                'plugin_id' => 'test_block_instantiation',
                'display_message' => 'foo text',
              ],
            ],
            'right' => [
              'bar' => [
                'plugin_id' => 'test_block_instantiation',
                'display_message' => 'bar text',
              ],
            ],
          ],
        ],
      ],
      [
        '.layout--onecol',
        '.layout--twocol',
      ],
      [
        'Powered by',
        'foo text',
        'bar text',
      ],
      'user.permissions',
      '',
      'MISS',
    ];
    return $data;
  }

  /**
   * Tests layout_section formatter output.
   *
   * @dataProvider providerTestLayoutSectionFormatter
   */
  public function testLayoutSectionFormatter($layout_data, $expected_selector, $expected_content, $expected_cache_contexts, $expected_cache_tags, $expected_dynamic_cache) {
    $this->createNode([
      'type' => 'bundle_with_section_field',
      'title' => 'The node title',
      'body' => [
        [
          'value' => 'The node body',
        ],
      ],
      $this->fieldName => $layout_data,
    ]);

    $this->drupalGet('node/1');
    $this->assertLayoutSection($expected_selector, $expected_content, $expected_cache_contexts, $expected_cache_tags, $expected_dynamic_cache);

    $this->drupalGet('node/1/layout');
    $this->assertLayoutSection($expected_selector, $expected_content, $expected_cache_contexts, $expected_cache_tags, $expected_dynamic_cache);
  }

  public function testLayoutSectionFormatterAccess() {
    $this->createNode([
      'type' => 'bundle_with_section_field',
      'title' => 'The node title',
      'body' => [
        [
          'value' => 'The node body',
        ],
      ],
      $this->fieldName => [
        [
          'layout' => 'layout_onecol',
          'section' => [
            'content' => [
              'baz' => [
                'plugin_id' => 'test_access',
              ],
            ],
          ],
        ],
      ],
    ]);

    // Restrict access to the block.
    $this->container->get('state')->set('test_block_access', FALSE);

    $this->drupalGet('node/1');
    $this->assertLayoutSection('.layout--onecol', NULL, '', '', 'UNCACHEABLE');
    // Ensure the block was not rendered.
    $this->assertSession()->pageTextNotContains('Hello test world');

    // Grant access to the block, and ensure it was rendered.
    $this->container->get('state')->set('test_block_access', TRUE);
    $this->drupalGet('node/1');
    $this->assertLayoutSection('.layout--onecol', 'Hello test world', '', '', 'UNCACHEABLE');
  }

  public function testLayoutUrlNoSectionField() {
    $this->createNode([
      'type' => 'bundle_without_section_field',
      'title' => 'The node title',
      'body' => [
        [
          'value' => 'The node body',
        ],
      ],
    ]);
    $this->drupalGet('node/1/layout');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Asserts the output of a layout section.
   *
   * @param string|array $expected_selector
   *   A selector or list of CSS selectors to find.
   * @param string|array $expected_content
   *   A string or list of strings to find.
   * @param string $expected_cache_contexts
   *   A string of cache contexts to be found in the header.
   * @param string $expected_cache_tags
   *   A string of cache tags to be found in the header.
   * @param string $expected_dynamic_cache
   *   The expected dynamic cache header. Either 'HIT', 'MISS' or 'UNCACHEABLE'.
   */
  protected function assertLayoutSection($expected_selector, $expected_content, $expected_cache_contexts = '', $expected_cache_tags = '', $expected_dynamic_cache = 'MISS') {
    $assert_session = $this->assertSession();
    // Find the given selector.
    foreach ((array) $expected_selector as $selector) {
      $element = $this->cssSelect($selector);
      $this->assertNotEmpty($element);
    }

    // Find the given content.
    foreach ((array) $expected_content as $content) {
      $assert_session->pageTextContains($content);
    }
    if ($expected_cache_contexts) {
      $assert_session->responseHeaderContains('X-Drupal-Cache-Contexts', $expected_cache_contexts);
    }
    if ($expected_cache_tags) {
      $assert_session->responseHeaderContains('X-Drupal-Cache-Tags', $expected_cache_tags);
    }
    $assert_session->responseHeaderEquals('X-Drupal-Dynamic-Cache', $expected_dynamic_cache);
  }

}
