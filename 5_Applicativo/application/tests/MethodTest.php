<?php

use models\Users;
use models\Methods;
use controller\Method;

require_once __DIR__ . '/support/CreatesTestData.php';

class MethodTest extends ControllerTestCase
{
    use CreatesTestData;

    private $user;

    protected function setUp(): void{
        parent::setUp();

        // BUG FIX: prima si usava l'utente reale con id 2: ora ogni test usa un utente di prova (eliminato nel tearDown)
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

    /** Crea un metodo di prova dell'utente loggato (o di $userId). */
    private function newMethod(string $name, string $type, ?int $userId = null): Methods
    {
        return Methods::create([
            'user_id'   => $userId ?? $this->user->id,
            'name'      => $name,
            'type'      => $type,
            'is_active' => 1,
        ]);
    }

    public function testIndexRendersMethods(): void
    {
        $this->newMethod('UnitTestMethodIndex', 'UnitTestMethodIndex');

        $method = new Method();
        $output = $this->assertNoRedirect(function () use ($method) {
            $method->index();
        });

        $this->assertNotEmpty($output);
    }

    public function testCreateRendersForm(): void
    {
        $method = new Method();
        $output = $this->assertNoRedirect(function () use ($method) {
            $method->create();
        });

        $this->assertNotEmpty($output);
    }

    public function testStoreMethod(){
        $_POST['name'] = 'UnitTestMethod';
        $_POST['type'] = 'UnitTestMethod';

        $method = new Method();

        $this->assertRedirectsTo(URL . 'method/index', function () use ($method){
            $method->store();
        });

        $this->assertEquals($_SESSION['success'], 'Metodo di pagamento aggiunto');
        $this->assertTrue(Methods::where('user_id', $this->user->id)->where('name', 'UnitTestMethod')->exists());
    }

    public function testStoreMethodWithInvalidName(){

        $initialCount = Methods::where('user_id', $this->user->id)->count();

        $_POST['name'] = '<script>{}</script>@@##';
        $_POST['type'] = 'UnitTestMethod';

        $method = new Method();

        $this->assertRedirectsTo(URL . 'method/create', function () use ($method){
            $method->store();
        });

        $this->assertArrayHasKey('name', $_SESSION['errors']);
        //$this->assertSame(0, Methods::where('user_id', $this->user->id)->count());

        $this->assertSame($initialCount, Methods::where('user_id', $this->user->id)->count());
    }

    public function testStoreMethodAlreadyExists(){
        $_POST['name'] = 'UnitTestMethodExist';
        $_POST['type'] = 'UnitTestMethodExist';

        $this->newMethod('UnitTestMethodExist', 'UnitTestMethodExist');

        $method = new Method();

        $this->assertRedirectsTo(URL . 'method/create', function () use ($method){
            $method->store();
        });

        $this->assertEquals('metodo di pagamento esistente', $_SESSION['errors']['generic'] );
        $this->assertSame(1, Methods::where('user_id', $this->user->id)->where('name', 'UnitTestMethodExist')->count());
    }

    public function testEditRendersForm(): void
    {
        $existing = $this->newMethod('UnitTestMethodEditForm', 'UnitTestMethodEditForm');
        $_POST['PaymentMethodsId'] = $existing->id;

        $method = new Method();
        $output = $this->assertNoRedirect(function () use ($method) {
            $method->edit();
        });

        $this->assertNotEmpty($output);
    }

    public function testEditMethodOfAnotherUserRedirects(): void
    {
        $other = $this->createUser();
        $foreign = $this->newMethod('UnitTestMethodAltrui', 'UnitTestMethodAltrui', $other->id);
        $_POST['PaymentMethodsId'] = $foreign->id;

        $method = new Method();
        $this->assertRedirectsTo(URL . 'method/index', function () use ($method) {
            $method->edit();
        });

        $this->assertArrayHasKey('generic', $_SESSION['errors']);
    }

    public function testUpdateMethod(){

        $existing = $this->newMethod('UnitTestMethodBeforeEdit', 'UnitTestMethodBeforeEdit');

        $_POST['PaymentMethodsId'] = $existing->id;
        $_POST['name'] = 'UnitTestMethodAfterEdit';
        $_POST['type'] = 'UnitTestMethodAfterEdit';

        $method = new Method();

        $this->assertRedirectsTo(URL . 'method/index', function () use ($method){
            $method->update();
        });

        $this->assertEquals($_SESSION['success'], 'Metodo di pagamento aggiornato');
        $this->assertSame('UnitTestMethodAfterEdit', Methods::find($existing->id)->name);
    }

    public function testUpdateMethodToSameName(){

        $this->newMethod('UnitTestMethodEdit', 'UnitTestMethodEdit');
        $existing = $this->newMethod('UnitTestMethodEditError', 'UnitTestMethodEditError');

        $_POST['PaymentMethodsId'] = $existing->id;
        $_POST['name'] = 'UnitTestMethodEdit';
        $_POST['type'] = 'UnitTestMethodEdit';

        $method = new Method();

        $this->assertRedirectsTo(URL . 'method/edit', function () use ($method){
            $method->update();
        });

        $this->assertEquals( 'Metodo di pagamento già esistente' , $_SESSION['errors']['generic'] );

        // BUG FIX nel controller: prima l'update veniva eseguito comunque. Il metodo non deve cambiare
        $this->assertSame('UnitTestMethodEditError', Methods::find($existing->id)->name);
        $this->assertArrayNotHasKey('success', $_SESSION);
    }

    public function testUpdateMethodOfAnotherUserIsDenied(){
        $other = $this->createUser();
        $foreign = $this->newMethod('UnitTestMethodAltrui', 'UnitTestMethodAltrui', $other->id);

        $_POST['PaymentMethodsId'] = $foreign->id;
        $_POST['name'] = 'UnitTestMethodRubato';
        $_POST['type'] = 'UnitTestMethodRubato';

        $method = new Method();

        $this->assertRedirectsTo(URL . 'method/edit', function () use ($method){
            $method->update();
        });

        $this->assertSame('UnitTestMethodAltrui', Methods::find($foreign->id)->name);
    }

    public function testDelateMethod(){
        $existing = $this->newMethod('methodDelete', 'methodDelete');

        $_POST['PaymentMethodsId'] = $existing->id;

        $method = new Method();

        $this->assertRedirectsTo(URL . 'method/index', function () use ($method){
            $method->delete();
        });

        // BUG FIX: il test usava exists()->is_active (exists() restituisce un booleano): si rilegge il record
        // Soft delete: il metodo resta nel DB con is_active = 0
        $deleted = Methods::find($existing->id);
        $this->assertNotNull($deleted);
        $this->assertEquals(0, $deleted->is_active);
    }

    public function testDeleteMethodOfAnotherUserIsDenied(){
        $other = $this->createUser();
        $foreign = $this->newMethod('UnitTestMethodAltrui', 'UnitTestMethodAltrui', $other->id);

        $_POST['PaymentMethodsId'] = $foreign->id;

        $method = new Method();

        $this->assertRedirectsTo(URL . 'method/index', function () use ($method){
            $method->delete();
        });

        $this->assertEquals(1, Methods::find($foreign->id)->is_active);
    }
}
