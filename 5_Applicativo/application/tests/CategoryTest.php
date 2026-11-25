<?php

use models\Users;
use models\Categories;
use controller\Category;

require_once __DIR__ . '/support/CreatesTestData.php';

class CategoryTest extends ControllerTestCase
{
    use CreatesTestData;

    private $user;

    protected function setUp(): void{
        parent::setUp();

        // BUG FIX: prima si usava l'utente reale con id 2 (findorFail): se mancava nel DB falliva tutto e i test
        // sporcavano i suoi dati. Ora ogni test lavora su un utente di prova che viene eliminato nel tearDown()
        $this->user = $this->createUser();

        $_SESSION['user'] = $this->user;
    }

    protected function tearDown(): void
    {
        $this->cleanTestData();
        $_POST = [];
        $_SESSION = [];
        parent::tearDown();
    }

    public function testIndexRendersCategories(): void
    {
        $this->createCategory($this->user->id);

        $category = new Category();
        $output = $this->assertNoRedirect(function () use ($category) {
            $category->index();
        });

        $this->assertNotEmpty($output);
    }

    public function testCreateRendersForm(): void
    {
        $category = new Category();
        $output = $this->assertNoRedirect(function () use ($category) {
            $category->create();
        });

        $this->assertNotEmpty($output);
    }

    public function testStoreCategory(){
        $_POST['name'] = 'UnitTestcategory';

        $categoty = new Category();

        $this->assertRedirectsTo(URL . 'category/index', function () use ($categoty) {
            $categoty->store();
        });

        $this->assertEquals($_SESSION['success'], 'Categoria creata con successo');
        $this->assertTrue(Categories::where('user_id', $this->user['id'])->where('name', 'UnitTestcategory')->exists());
    }

    public function testStoreCategoryInvalidName(){
        // BUG FIX: 'UnitTest' è un nome normale: per avere un nome non valido servono caratteri non ammessi
        $_POST['name'] = '<script>{}</script>@@##';

        $category = new Category();

        $this->assertRedirectsTo(URL . 'category/create', function () use ($category) {
            $category->store();
        });

        $this->assertArrayHasKey('name', $_SESSION['errors']);
    }

    public function testStoreCategoryDuplicated(){

        $this->createCategory($this->user->id, true, 'Salamalekum');

        $_POST['name'] = 'Salamalekum';

        $category = new Category();

        $this->assertRedirectsTo(URL . 'category/create', function () use ($category) {
            $category->store();
        });

        $this->assertArrayHasKey('duplicated_name', $_SESSION['errors']);

    }

    public function testStoreCategoryReactivatesDisabledOne(){
        $disabled = $this->createCategory($this->user->id, false, 'UnitTestDisabilitata');

        $_POST['name'] = 'UnitTestDisabilitata';

        $category = new Category();

        $this->assertRedirectsTo(URL . 'category/index', function () use ($category) {
            $category->store();
        });

        // Una categoria disabilitata con lo stesso nome viene riattivata, non duplicata
        $this->assertEquals(1, Categories::find($disabled->id)->is_active);
        $this->assertSame(1, Categories::where('user_id', $this->user->id)->where('name', 'UnitTestDisabilitata')->count());
    }

    public function testEditRendersForm(): void
    {
        $existing = $this->createCategory($this->user->id);
        $_POST['id_category'] = $existing->id;

        $category = new Category();
        $output = $this->assertNoRedirect(function () use ($category) {
            $category->edit();
        });

        $this->assertNotEmpty($output);
    }

    public function testEditNonExistingCategoryRedirects(): void
    {
        $_POST['id_category'] = -1;

        $category = new Category();
        $this->assertRedirectsTo(URL . 'category/index', function () use ($category) {
            $category->edit();
        });
    }

    public function testUpdateCategory(){
        $existing = Categories::create(['user_id' => $this->user['id'], 'name' => 'UnitTestPrima', 'is_active' => 1]);

        $_POST['id_category'] = $existing->id;
        $_POST['name'] = 'UnitTestDopo';

        $category = new Category();

        $this->assertRedirectsTo(URL . 'category/index', function () use ($category) {
            $category->update();
        });

        $this->assertSame('Categoria modificata con successo', $_SESSION['success']);
        $this->assertSame('UnitTestDopo', Categories::find($existing->id)->name);
    }

    public function testUpdateCategoryWithDuplicatedName(){
        $this->createCategory($this->user->id, true, 'UnitTestEsistente');
        $existing = $this->createCategory($this->user->id, true, 'UnitTestDaModificare');

        $_POST['id_category'] = $existing->id;
        $_POST['name'] = 'UnitTestEsistente';

        $category = new Category();

        $this->assertRedirectsTo(URL . 'category/edit/' . $existing->id, function () use ($category) {
            $category->update();
        });

        $this->assertArrayHasKey('duplicated_name', $_SESSION['errors']);
        $this->assertSame('UnitTestDaModificare', Categories::find($existing->id)->name);
    }

    public function testUpdateCategoryOfAnotherUserIsDenied(){
        $other = $this->createUser();
        $foreign = $this->createCategory($other->id, true, 'UnitTestAltrui');

        $_POST['id_category'] = $foreign->id;
        $_POST['name'] = 'UnitTestRubata';

        $category = new Category();

        $this->assertRedirectsTo(URL . 'category/index', function () use ($category) {
            $category->update();
        });

        $this->assertSame('UnitTestAltrui', Categories::find($foreign->id)->name);
    }

    public function testDeleteCategory(){
        $existing = Categories::create(['user_id' => $this->user['id'], 'name' => 'UnitTestElimina', 'is_active' => 1]);

        $_POST['id_category'] = $existing->id;

        $category = new Category();

        $this->assertRedirectsTo(URL . 'category/index', function () use ($category) {
            $category->delete();
        });

        $this->assertSame('Categoria eliminata con successo', $_SESSION['success']);
        $this->assertEquals(0, Categories::find($existing->id)->is_active);
    }

    public function testDeleteCategoryOfAnotherUserIsDenied(){
        $other = $this->createUser();
        $foreign = $this->createCategory($other->id, true, 'UnitTestAltrui');

        $_POST['id_category'] = $foreign->id;

        $category = new Category();

        $this->assertRedirectsTo(URL . 'category/index', function () use ($category) {
            $category->delete();
        });

        $this->assertEquals(1, Categories::find($foreign->id)->is_active);
    }
}
