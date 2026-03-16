<?php

namespace Tests\Unit;

use App\Gateways\GatewayFactory;
use App\Gateways\PaymentGatewayInterface;
use App\Models\Client;
use App\Models\Gateway;
use App\Models\Product;
use App\Models\Transaction;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_succeeds_on_first_gateway(): void
    {
        $gateway1 = Gateway::create(['name' => 'gateway_one', 'priority' => 1, 'is_active' => true]);
        Gateway::create(['name' => 'gateway_two', 'priority' => 2, 'is_active' => true]);
        $product = Product::create(['name' => 'Test Product', 'amount' => 1000]);

        $mockGatewayService = Mockery::mock(PaymentGatewayInterface::class);
        $mockGatewayService->shouldReceive('pay')->once()->andReturn([
            'success' => true,
            'external_id' => 'ext_123',
            'message' => 'OK',
        ]);

        $factory = Mockery::mock(GatewayFactory::class);
        $factory->shouldReceive('make')
            ->with(Mockery::on(fn ($g) => $g->id === $gateway1->id))
            ->andReturn($mockGatewayService);

        $service = new PaymentService($factory);

        $result = $service->purchase([
            'name' => 'Tester',
            'email' => 'tester@email.com',
            'card_number' => '5569000000006063',
            'cvv' => '010',
            'products' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('transactions', [
            'external_id' => 'ext_123',
            'status' => 'approved',
            'gateway_id' => $gateway1->id,
            'amount' => 1000,
            'card_last_numbers' => '6063',
        ]);
    }

    public function test_purchase_falls_back_to_second_gateway_when_first_fails(): void
    {
        $gateway1 = Gateway::create(['name' => 'gateway_one', 'priority' => 1, 'is_active' => true]);
        $gateway2 = Gateway::create(['name' => 'gateway_two', 'priority' => 2, 'is_active' => true]);
        $product = Product::create(['name' => 'Test Product', 'amount' => 1000]);

        $failingGateway = Mockery::mock(PaymentGatewayInterface::class);
        $failingGateway->shouldReceive('pay')->once()->andReturn([
            'success' => false,
            'external_id' => null,
            'message' => 'Gateway 1 failed',
        ]);

        $successGateway = Mockery::mock(PaymentGatewayInterface::class);
        $successGateway->shouldReceive('pay')->once()->andReturn([
            'success' => true,
            'external_id' => 'ext_456',
            'message' => 'OK',
        ]);

        $factory = Mockery::mock(GatewayFactory::class);
        $factory->shouldReceive('make')
            ->with(Mockery::on(fn ($g) => $g->id === $gateway1->id))
            ->andReturn($failingGateway);
        $factory->shouldReceive('make')
            ->with(Mockery::on(fn ($g) => $g->id === $gateway2->id))
            ->andReturn($successGateway);

        $service = new PaymentService($factory);

        $result = $service->purchase([
            'name' => 'Tester',
            'email' => 'tester@email.com',
            'card_number' => '5569000000006063',
            'cvv' => '010',
            'products' => [['product_id' => $product->id, 'quantity' => 2]],
        ]);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('transactions', [
            'gateway_id' => $gateway2->id,
            'external_id' => 'ext_456',
            'status' => 'approved',
            'amount' => 2000,
        ]);
    }

    public function test_purchase_fails_when_all_gateways_fail(): void
    {
        $gateway1 = Gateway::create(['name' => 'gateway_one', 'priority' => 1, 'is_active' => true]);
        $gateway2 = Gateway::create(['name' => 'gateway_two', 'priority' => 2, 'is_active' => true]);
        $product = Product::create(['name' => 'Test Product', 'amount' => 1000]);

        $failingGateway1 = Mockery::mock(PaymentGatewayInterface::class);
        $failingGateway1->shouldReceive('pay')->once()->andReturn([
            'success' => false,
            'external_id' => null,
            'message' => 'Failed 1',
        ]);

        $failingGateway2 = Mockery::mock(PaymentGatewayInterface::class);
        $failingGateway2->shouldReceive('pay')->once()->andReturn([
            'success' => false,
            'external_id' => null,
            'message' => 'Failed 2',
        ]);

        $factory = Mockery::mock(GatewayFactory::class);
        $factory->shouldReceive('make')
            ->with(Mockery::on(fn ($g) => $g->id === $gateway1->id))
            ->andReturn($failingGateway1);
        $factory->shouldReceive('make')
            ->with(Mockery::on(fn ($g) => $g->id === $gateway2->id))
            ->andReturn($failingGateway2);

        $service = new PaymentService($factory);

        $result = $service->purchase([
            'name' => 'Tester',
            'email' => 'tester@email.com',
            'card_number' => '5569000000006063',
            'cvv' => '010',
            'products' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals('All gateways failed.', $result['message']);
        $this->assertCount(2, $result['errors']);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_purchase_fails_when_no_active_gateways(): void
    {
        Gateway::create(['name' => 'gateway_one', 'priority' => 1, 'is_active' => false]);
        Product::create(['name' => 'Test Product', 'amount' => 1000]);

        $factory = Mockery::mock(GatewayFactory::class);
        $service = new PaymentService($factory);

        $result = $service->purchase([
            'name' => 'Tester',
            'email' => 'tester@email.com',
            'card_number' => '5569000000006063',
            'cvv' => '010',
            'products' => [['product_id' => 1, 'quantity' => 1]],
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals('No active gateways available.', $result['message']);
    }

    public function test_refund_succeeds(): void
    {
        $gateway = Gateway::create(['name' => 'gateway_one', 'priority' => 1, 'is_active' => true]);
        $client = Client::create(['name' => 'Tester', 'email' => 'tester@email.com']);
        $transaction = Transaction::create([
            'client_id' => $client->id,
            'gateway_id' => $gateway->id,
            'external_id' => 'ext_789',
            'status' => 'approved',
            'amount' => 1000,
            'card_last_numbers' => '6063',
        ]);

        $mockGatewayService = Mockery::mock(PaymentGatewayInterface::class);
        $mockGatewayService->shouldReceive('refund')
            ->with('ext_789')
            ->once()
            ->andReturn(['success' => true, 'message' => 'Refunded']);

        $factory = Mockery::mock(GatewayFactory::class);
        $factory->shouldReceive('make')->andReturn($mockGatewayService);

        $service = new PaymentService($factory);
        $result = $service->refund($transaction);

        $this->assertTrue($result['success']);
        $this->assertEquals('refunded', $transaction->fresh()->status);
    }

    public function test_refund_fails_when_already_refunded(): void
    {
        $gateway = Gateway::create(['name' => 'gateway_one', 'priority' => 1, 'is_active' => true]);
        $client = Client::create(['name' => 'Tester', 'email' => 'tester@email.com']);
        $transaction = Transaction::create([
            'client_id' => $client->id,
            'gateway_id' => $gateway->id,
            'external_id' => 'ext_789',
            'status' => 'refunded',
            'amount' => 1000,
            'card_last_numbers' => '6063',
        ]);

        $factory = Mockery::mock(GatewayFactory::class);
        $service = new PaymentService($factory);

        $result = $service->refund($transaction);

        $this->assertFalse($result['success']);
        $this->assertEquals('Transaction already refunded.', $result['message']);
    }
}
