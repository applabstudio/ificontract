<?php

namespace Drupal\paragraphs\Tests\Classic;

/**
 * Tests asymmetric translation of paragraphs.
 *
 * @group paragraphs
 */
class ParagraphsAsymmetricTranslationTest extends ParagraphsTestBase {

  public static $modules = [
    'node',
    'paragraphs_demo',
    'content_translation',
    'block',
  ];

  /**
   * A user with admin permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('page_title_block');

    $this->adminUser = $this->drupalCreateUser(
      [
        'administer site configuration',
        'administer nodes',
        'create paragraphed_content_demo content',
        'edit any paragraphed_content_demo content',
        'delete any paragraphed_content_demo content',
        'administer paragraph form display',
        'administer node form display',
        'administer paragraph fields',
        'administer content translation',
        'translate any entity',
        'create content translations',
        'administer languages',
        'administer content types',
      ]
    );

    $this->drupalLogin($this->adminUser);

    // Mark the paragraph entities as untranslatable and the paragraph field
    // as translatable.
    $edit = [
      'entity_types[paragraph]' => TRUE,
      'settings[node][paragraphed_content_demo][fields][field_paragraphs_demo]' => TRUE,
      'settings[paragraph][nested_paragraph][translatable]' => TRUE,
      'settings[paragraph][nested_paragraph][settings][language][language_alterable]' => FALSE,
      'settings[paragraph][text][translatable]' => TRUE,
      'settings[paragraph][text][settings][language][language_alterable]' => FALSE,
      'settings[paragraph][nested_paragraph][fields][field_paragraphs_demo]' => TRUE,
    ];
    $this->drupalPostForm(
      'admin/config/regional/content-language',
      $edit,
      t('Save configuration')
    );
  }

  /**
   * Test asymmetric translation.
   *
   * 1. Translate node and create different paragraphs.
   * 2. Update the paragraphs on each translation.
   * 3. Add different number of paragraphs on each translation.
   * 4. Remove paragraphs from each translation.
   * 5. Reorder the paragraphs on each translation.
   */
  public function testParagraphsMultilingualFieldTranslation() {
    // Edit widget to classic.
    $this->drupalGet('/admin/structure/types/manage/paragraphed_content_demo/form-display');
    $this->drupalPostForm(NULL, array('fields[field_paragraphs_demo][type]' => 'entity_reference_paragraphs'), t('Save'));

    // 1. Translate node and create different paragraphs.
    // Add an English node.
    $this->drupalGet('node/add/paragraphed_content_demo');
    $this->drupalPostForm(NULL, NULL, t('Add Text'));

    $edit = [
      'title[0][value]' => 'Title in english',
      'field_paragraphs_demo[0][subform][field_text_demo][0][value]' => 'Text in english',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Translate the node to French.
    $node = $this->drupalGetNodeByTitle('Title in english');
    $this->drupalGet('node/' . $node->id() . '/translations/add/en/fr');

    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_demo_0_edit');

    $edit = [
      'title[0][value]' => 'Title in french',
      'field_paragraphs_demo[0][subform][field_text_demo][0][value]' => 'Text in french',
      'revision' => TRUE,
      'revision_log[0][value]' => 'french 1',
    ];
    $this->drupalPostForm(
      NULL,
      $edit,
      t('Save (this translation)')
    );

    // Check the english translation.
    $this->drupalGet('node/' . $node->id());
    $this->assertText('Title in english');
    $this->assertText('Text in english');
    $this->assertNoText('Title in french');
    $this->assertNoText('Text in french');

    // Check the french translation.
    $this->drupalGet('fr/node/' . $node->id());
    $this->assertText('Title in french');
    $this->assertText('Text in french');
    $this->assertNoText('Title in english');
    $this->assertNoText('Text in english');

    // Check the db.
    $select = \Drupal::database()->select('node__field_paragraphs_demo', 'n');
    $select->innerJoin('paragraphs_item', 'p', 'p.id = n.field_paragraphs_demo_target_id');
    $select->addField('p', 'langcode');
    $select->condition('n.entity_id', $node->id());
    $paragraph_langcodes = $select->execute()->fetchCol();

    $this->assertEqual(
      $paragraph_langcodes,
      ['en', 'fr'],
      'Translated paragraphs are separate entities'
    );

    // 2. Update the paragraphs on each translation.
    // Try to edit the paragraphs, to see if the correct translation gets
    // updated. Start with the english.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_demo_0_edit');
    $edit = [
      'field_paragraphs_demo[0][subform][field_text_demo][0][value]' => 'The updated english text',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save (this translation)'));
    // Check if only the english node had its paragraph text updated, and that
    // there has been no mixing-up of the paragraph entities.
    $this->drupalGet('node/' . $node->id());
    $this->assertText('The updated english text');
    $this->assertNoText('Text in english');
    $this->assertNoText('Text in french');

    $this->drupalGet('fr/node/' . $node->id());
    $this->assertText('Text in french');
    $this->assertNoText('Text in english');
    $this->assertNoText('The updated english text');

    // Now repeat for the french.
    $this->drupalGet('fr/node/' . $node->id() . '/edit');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_demo_0_edit');
    $edit = [
      'field_paragraphs_demo[0][subform][field_text_demo][0][value]' => 'The updated french text',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save (this translation)'));
    // Check if only the french node had its paragraph text updated, and that
    // there has been no mixing-up of the paragraph entities.
    $this->drupalGet('node/' . $node->id());
    $this->assertText('The updated english text');
    $this->assertNoText('Text in french');
    $this->assertNoText('The updated french text');

    $this->drupalGet('fr/node/' . $node->id());
    $this->assertText('The updated french text');
    $this->assertNoText('Text in french');
    $this->assertNoText('The updated english text');

    // 3. Add different number of paragraphs on each translation.
    // Add 2 more paragraphs on the english node.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->drupalPostForm(NULL, NULL, t('Add Text'));
    $this->drupalPostForm(NULL, NULL, t('Add Text'));
    $edit = [
      'field_paragraphs_demo[1][subform][field_text_demo][0][value]' => 'Second text in english',
      'field_paragraphs_demo[2][subform][field_text_demo][0][value]' => 'Third text in english',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save (this translation)'));

    // Confirm that the english node has the new paragraphs, and the french
    // node is intact.
    $this->drupalGet('node/' . $node->id());
    $this->assertText('The updated english text');
    $this->assertText('Second text in english');
    $this->assertText('Third text in english');
    $this->assertNoText('The updated french text');

    $this->drupalGet('fr/node/' . $node->id());
    $this->assertText('The updated french text');
    $this->assertNoText('The updated english text');
    $this->assertNoText('Second text in english');
    $this->assertNoText('Third text in english');

    // Repeat the same, this time adding 3 new paragraphs on the french node.
    $this->drupalGet('fr/node/' . $node->id() . '/edit');
    $this->drupalPostForm(NULL, NULL, t('Add Text'));
    $this->drupalPostForm(NULL, NULL, t('Add Text'));
    $this->drupalPostForm(NULL, NULL, t('Add Text'));
    $edit = [
      'field_paragraphs_demo[1][subform][field_text_demo][0][value]' => 'Second text in french',
      'field_paragraphs_demo[2][subform][field_text_demo][0][value]' => 'Third text in french',
      'field_paragraphs_demo[3][subform][field_text_demo][0][value]' => 'Fourth text in french',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save (this translation)'));

    // Confirm that the french node has the new paragraphs, and the english
    // node is intact.
    $this->drupalGet('node/' . $node->id());
    $this->assertText('The updated english text');
    $this->assertText('Second text in english');
    $this->assertText('Third text in english');
    $this->assertNoText('The updated french text');
    $this->assertNoText('Second text in french');
    $this->assertNoText('Third text in french');
    $this->assertNoText('Fourth text in french');

    $this->drupalGet('fr/node/' . $node->id());
    $this->assertText('The updated french text');
    $this->assertText('Second text in french');
    $this->assertText('Third text in french');
    $this->assertText('Fourth text in french');
    $this->assertNoText('The updated english text');
    $this->assertNoText('Second text in english');
    $this->assertNoText('Third text in english');

    // 4. Remove paragraphs from each translation.
    // Delete one of the paragraphs on the english node, and confirm that the
    // rest are intact, and also that the french node is intact.
    $this->drupalGet('node/' . $node->id() . '/edit');

    $this->assertNotNull($this->xpath('//*[@name="field_paragraphs_demo_1_remove"]'));
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_demo_1_remove');
    $this->assertNotNull($this->xpath('//*[@name="field_paragraphs_demo_1_confirm_remove"]'));
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_demo_1_confirm_remove');
    $this->drupalPostForm(NULL, NULL, t('Save (this translation)'));

    $this->drupalGet('node/' . $node->id());
    $this->assertText('The updated english text');
    $this->assertNoText('Second text in english');
    $this->assertText('Third text in english');
    $this->assertNoText('The updated french text');
    $this->assertNoText('Second text in french');
    $this->assertNoText('Third text in french');
    $this->assertNoText('Fourth text in french');

    $this->drupalGet('fr/node/' . $node->id());
    $this->assertNoText('The updated english text');
    $this->assertNoText('Second text in english');
    $this->assertNoText('Third text in english');
    $this->assertText('The updated french text');
    $this->assertText('Second text in french');
    $this->assertText('Third text in french');
    $this->assertText('Fourth text in french');

    // Repeat the same for the french node, deleting 2 paragraphs now.
    $this->drupalGet('fr/node/' . $node->id() . '/edit');

    $this->assertNotNull($this->xpath('//*[@name="field_paragraphs_demo_1_remove"]'));
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_demo_1_remove');
    $this->assertNotNull($this->xpath('//*[@name="field_paragraphs_demo_1_confirm_remove"]'));
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_demo_1_confirm_remove');
    $this->assertNotNull($this->xpath('//*[@name="field_paragraphs_demo_3_remove"]'));
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_demo_3_remove');
    $this->assertNotNull($this->xpath('//*[@name="field_paragraphs_demo_3_confirm_remove"]'));
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_demo_3_confirm_remove');
    $this->drupalPostForm(NULL, NULL, t('Save (this translation)'));

    $this->drupalGet('fr/node/' . $node->id());
    $this->assertNoText('The updated english text');
    $this->assertNoText('Second text in english');
    $this->assertNoText('Third text in english');
    $this->assertText('The updated french text');
    $this->assertNoText('Second text in french');
    $this->assertText('Third text in french');
    $this->assertNoText('Fourth text in french');

    $this->drupalGet('node/' . $node->id());
    $this->assertText('The updated english text');
    $this->assertNoText('Second text in english');
    $this->assertText('Third text in english');
    $this->assertNoText('The updated french text');
    $this->assertNoText('Second text in french');
    $this->assertNoText('Third text in french');
    $this->assertNoText('Fourth text in french');

    // 5. Reorder the paragraphs on each translation.
    // Reminder: This is our current content
    //
    // - English node
    // -- The updated english text.
    // -- Third text in english.
    //
    // - French node
    // -- The updated french text.
    // -- Third text in french.
    // First check the english node, that "The updated english text" is indeed
    // before the "Third text in english", as it has been set on the previous
    // steps.
    $this->drupalGet('node/' . $node->id());
    $regex = '/The updated english text.*Third text in english/s';
    $this->assertPattern($regex);

    $this->drupalGet('fr/node/' . $node->id());
    $regex = '/The updated french text.*Third text in french/s';
    $this->assertPattern($regex);

    // Reorder the paragraphs in the english node, and check if it applied
    // correctly. Check also that the french node is intact.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $edit = [
      'field_paragraphs_demo[0][_weight]' => 2,
      'field_paragraphs_demo[1][_weight]' => -2,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save (this translation)'));

    $this->drupalGet('node/' . $node->id());
    $regex = '/Third text in english.*The updated english text/s';
    $this->assertPattern($regex);

    $this->drupalGet('fr/node/' . $node->id());
    $regex = '/The updated french text.*Third text in french/s';
    $this->assertPattern($regex);

    // And now reorder the french node, and then confirm that the new order
    // applied correctly, and that the english node is intact.
    $this->drupalGet('fr/node/' . $node->id() . '/edit');
    $edit = [
      'field_paragraphs_demo[0][_weight]' => 2,
      'field_paragraphs_demo[1][_weight]' => -2,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save (this translation)'));

    $this->drupalGet('fr/node/' . $node->id());
    $regex = '/Third text in french.*The updated french text/s';
    $this->assertPattern($regex);

    $this->drupalGet('node/' . $node->id());
    $regex = '/Third text in english.*The updated english text/s';
    $this->assertPattern($regex);
  }

  /**
   * Test paragraphs with nested multilingual fields.
   */
  public function testParagraphsMultilingualFieldTranslationNested() {
    // Edit widget to classic.
    $this->drupalGet('/admin/structure/types/manage/paragraphed_content_demo/form-display');
    $this->drupalPostForm(NULL, array('fields[field_paragraphs_demo][type]' => 'entity_reference_paragraphs'), t('Save'));

    // 1. Translate node and create different paragraphs.
    // Add an English node.
    $this->drupalGet('node/add/paragraphed_content_demo');
    $this->drupalPostForm(NULL, NULL, t('Add Nested Paragraph'), array(), array(), '');
    $this->drupalPostAjaxForm(NULL, NULL, 'field_paragraphs_demo_0_subform_field_paragraphs_demo_text_add_more');
    $edit = [
      'title[0][value]' => 'Title in english',
      'field_paragraphs_demo[0][subform][field_paragraphs_demo][0][subform][field_text_demo][0][value]' => 'Text in english',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Translate the node to French.
    $node = $this->drupalGetNodeByTitle('Title in english');
    $this->drupalGet('node/' . $node->id() . '/translations/add/en/fr');

    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_demo_0_edit');

    $edit = [
      'title[0][value]' => 'Title in french',
      'field_paragraphs_demo[0][subform][field_paragraphs_demo][0][subform][field_text_demo][0][value]' => 'Text in french',
    ];
    $this->drupalPostForm(
      NULL,
      $edit,
      t('Save (this translation)')
    );

    // Check the english translation.
    $this->drupalGet('node/' . $node->id());
    $this->assertText('Title in english');
    $this->assertText('Text in english');
    $this->assertNoText('Title in french');
    $this->assertNoText('Text in french');

    // Check the french translation.
    $this->drupalGet('fr/node/' . $node->id());
    $this->assertText('Title in french');
    $this->assertText('Text in french');
    $this->assertNoText('Title in english');
    $this->assertNoText('Text in english');

    // 2. Update the paragraphs.
    // Try to edit the paragraphs, to see if the correct translation gets
    // updated. Start with the english.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_demo_0_edit');
    $edit = [
      'field_paragraphs_demo[0][subform][field_paragraphs_demo][0][subform][field_text_demo][0][value]' => 'The updated english text',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save (this translation)'));
    // Check if only the english node had its paragraph text updated, and that
    // there has been no mixing-up of the paragraph entities.
    $this->drupalGet('node/' . $node->id());
    $this->assertText('The updated english text');
    $this->assertNoText('Text in english');
    $this->assertNoText('Text in french');

    $this->drupalGet('fr/node/' . $node->id());
    $this->assertText('Text in french');
    $this->assertNoText('Text in english');
    $this->assertNoText('The updated english text');

    // 3. Add different number of paragraphs on one translation.
    // Add one more paragraph on the english node.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_demo_0_edit');
    $this->drupalPostAjaxForm(NULL, NULL, 'field_paragraphs_demo_0_subform_field_paragraphs_demo_text_add_more');
    $edit = [
      'field_paragraphs_demo[0][subform][field_paragraphs_demo][1][subform][field_text_demo][0][value]' => 'New english text',
    ];
    $this->drupalPostForm(
      NULL,
      $edit,
      t('Save (this translation)')
    );

    // Confirm that the english node has the new paragraphs, and the french
    // node is intact.
    $this->drupalGet('node/' . $node->id());
    $this->assertText('The updated english text');
    $this->assertText('New english text');
    $this->assertNoText('Text in french');

    $this->drupalGet('fr/node/' . $node->id());
    $this->assertText('Text in french');
    $this->assertNoText('The updated english text');
    $this->assertNoText('New english text');
  }

  /**
   * Test paragraph multilingual field deletes.
   */
  public function testParagraphsMultilingualFieldDeleteTranslation() {
    // Edit widget to classic.
    $this->drupalGet('/admin/structure/types/manage/paragraphed_content_demo/form-display');
    $this->drupalPostForm(NULL, array('fields[field_paragraphs_demo][type]' => 'entity_reference_paragraphs'), t('Save'));

    // 1. Translate node and create different paragraphs. Delete the translation
    // and check if the original is intact.
    // Add an English node.
    $this->drupalGet('node/add/paragraphed_content_demo');
    $this->drupalPostForm(NULL, NULL, t('Add Nested Paragraph'), array(), array(), '');
    $this->drupalPostAjaxForm(NULL, NULL, 'field_paragraphs_demo_0_subform_field_paragraphs_demo_text_add_more');
    $edit = [
      'title[0][value]' => 'Title in english',
      'field_paragraphs_demo[0][subform][field_paragraphs_demo][0][subform][field_text_demo][0][value]' => 'Text in english',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Translate the node to French.
    $node = $this->drupalGetNodeByTitle('Title in english');
    $this->drupalGet('node/' . $node->id() . '/translations/add/en/fr');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_demo_0_edit');

    $edit = [
      'title[0][value]' => 'Title in french',
      'field_paragraphs_demo[0][subform][field_paragraphs_demo][0][subform][field_text_demo][0][value]' => 'Text in french',
    ];
    $this->drupalPostForm(
      NULL,
      $edit,
      t('Save (this translation)')
    );

    // Check the english translation.
    $this->drupalGet('node/' . $node->id());
    $this->assertText('Title in english');
    $this->assertText('Text in english');
    $this->assertNoText('Title in french');
    $this->assertNoText('Text in french');

    // Check the french translation.
    $this->drupalGet('fr/node/' . $node->id());
    $this->assertText('Title in french');
    $this->assertText('Text in french');
    $this->assertNoText('Title in english');
    $this->assertNoText('Text in english');

    // Now delete the french translation.
    $this->drupalGet('fr/node/' . $node->id() . '/delete');
    $this->drupalPostForm(NULL, NULL, t('Delete French translation'));

    // Check the english translation.
    $this->drupalGet('node/' . $node->id());
    $this->assertText('Title in english');
    $this->assertText('Text in english');
    $this->assertNoText('Title in french');
    $this->assertNoText('Text in french');
  }

}
