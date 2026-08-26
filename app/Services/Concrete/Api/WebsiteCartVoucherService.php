<?php



namespace App\Services\Concrete\Api;



use App\Models\OrderSource;

use App\Models\OrderType;

use App\Models\Voucher;

use App\Models\WebsiteCart;

use App\Services\Concrete\Admin\OrderService;

use App\Services\Concrete\Admin\OrderSourceService;

use App\Services\Concrete\Admin\OrderTypeService;

use App\Services\Concrete\Admin\VoucherService;

use Exception;



/**

 * Website cart voucher preview/apply using the same OrderService logic as POS.

 */

class WebsiteCartVoucherService

{

    protected $order_service;

    protected $voucher_service;

    protected $order_type_service;

    protected $order_source_service;



    public function __construct(

        OrderService $order_service,

        VoucherService $voucher_service,

        OrderTypeService $order_type_service,

        OrderSourceService $order_source_service

    ) {

        $this->order_service = $order_service;

        $this->voucher_service = $voucher_service;

        $this->order_type_service = $order_type_service;

        $this->order_source_service = $order_source_service;

    }



    public function resolveWebsiteOrderContext(string $business_id): array

    {

        $this->order_type_service->seedDefaults($business_id);

        $this->order_source_service->seedDefaults($business_id);



        $order_type_id = OrderType::where('business_id', $business_id)

            ->where('code', 'DELIVERY')

            ->where('is_deleted', 0)

            ->value('order_type_id');



        $order_source_id = OrderSource::where('business_id', $business_id)

            ->where('code', 'WEBSITE')

            ->where('is_deleted', 0)

            ->value('order_source_id');



        return [

            'order_type_id' => $order_type_id,

            'order_source_id' => $order_source_id,

        ];

    }



    public function buildPreviewPayload(

        WebsiteCart $cart,

        array $items,

        array $context,

        ?string $sale_type_id,

        ?string $voucher_code = null,

        ?string $voucher_id = null,

        ?string $payment_method_id = null

    ): array {

        $order_ctx = $this->resolveWebsiteOrderContext($cart->business_id);



        $products = [];

        foreach ($items as $item) {

            $products[] = [

                'product_id' => $item['product_id'],

                'product_variation_id' => $item['product_variation_id'],

                'quantity' => $item['quantity'],

                'sale_type_id' => $sale_type_id,

            ];

        }



        $payload = [

            'business_id' => $cart->business_id,

            'branch_id' => $context['branch_id'] ?? $cart->branch_id,

            'warehouse_id' => $context['warehouse_id'] ?? null,

            'customer_id' => $cart->user_id,

            'order_type_id' => $order_ctx['order_type_id'],

            'order_source_id' => $order_ctx['order_source_id'],

            'sale_type_id' => $sale_type_id,

            'products' => $products,

        ];



        if ($voucher_id) {

            $payload['voucher_id'] = $voucher_id;

        } elseif ($voucher_code) {

            $payload['voucher_code'] = $voucher_code;

        }



        if ($payment_method_id) {

            $payload['payments'] = [['payment_method_id' => $payment_method_id]];

        }



        return $payload;

    }



    /**

     * @return array{voucher: ?array, preview: ?array, error: ?string}

     */

    public function previewForCart(

        WebsiteCart $cart,

        array $items,

        array $context,

        ?string $sale_type_id,

        ?string $payment_method_id = null

    ): array {

        if (empty($cart->voucher_id) && empty($cart->voucher_code)) {

            return ['voucher' => null, 'preview' => null, 'error' => null];

        }



        if (empty($items)) {

            return [

                'voucher' => $this->formatVoucherMeta($cart),

                'preview' => null,

                'error' => 'Add items to your cart before applying a voucher.',

            ];

        }



        try {

            $payload = $this->buildPreviewPayload(

                $cart,

                $items,

                $context,

                $sale_type_id,

                $cart->voucher_code,

                $cart->voucher_id,

                $payment_method_id

            );



            $preview = $this->order_service->previewVoucher($payload);

            $voucher = Voucher::find($cart->voucher_id)

                ?: $this->voucher_service->findByCode($cart->voucher_code, $cart->business_id);



            return [

                'voucher' => $this->formatVoucherFromModel($voucher, $preview),

                'preview' => $preview,

                'error' => null,

            ];

        } catch (Exception $e) {

            return [

                'voucher' => $this->formatVoucherMeta($cart),

                'preview' => null,

                'error' => $e->getMessage(),

            ];

        }

    }



    public function search(string $term, string $business_id): array

    {

        return $this->voucher_service->searchActive($term, $business_id)

            ->map(function ($voucher) {

                return [

                    'voucher_id' => $voucher->voucher_id,

                    'code' => $voucher->code,

                    'name' => $voucher->name,

                    'rule' => $voucher->describeRule(),

                ];

            })

            ->values()

            ->all();

    }



    public function eligible(WebsiteCart $cart, array $context, ?string $sale_type_id): array

    {

        $order_ctx = $this->resolveWebsiteOrderContext($cart->business_id);



        return $this->voucher_service->eligibleForCart([

            'business_id' => $cart->business_id,

            'branch_id' => $context['branch_id'] ?? $cart->branch_id,

            'user_id' => $cart->user_id,

            'order_type_id' => $order_ctx['order_type_id'],

            'order_source_id' => $order_ctx['order_source_id'],

            'sale_type_id' => $sale_type_id,

        ])->map(function ($voucher) {

            return [

                'voucher_id' => $voucher->voucher_id,

                'code' => $voucher->code,

                'name' => $voucher->name,

                'rule' => $voucher->describeRule(),

            ];

        })->values()->all();

    }



    /**

     * Validate and persist voucher on cart. Throws on invalid voucher.

     */

    public function apply(WebsiteCart $cart, array $items, array $context, ?string $sale_type_id, ?string $code, ?string $voucher_id): WebsiteCart

    {

        if (empty($items)) {

            throw new Exception('Add items to your cart before applying a voucher.');

        }



        $code = trim((string) $code);

        if ($code === '' && empty($voucher_id)) {

            throw new Exception('Please enter a voucher code.');

        }



        $preview_payload = $this->buildPreviewPayload(

            $cart,

            $items,

            $context,

            $sale_type_id,

            $code ?: null,

            $voucher_id,

        );



        $preview = $this->order_service->previewVoucher($preview_payload);



        if ((float) ($preview['voucher_discount_amount'] ?? 0) <= 0 && empty($preview['lines'])) {

            throw new Exception('This voucher does not apply to your current cart.');

        }



        $voucher = !empty($voucher_id)

            ? Voucher::find($voucher_id)

            : $this->voucher_service->findByCode($code, $cart->business_id);



        if (!$voucher) {

            throw new Exception('The voucher code was not found.');

        }



        $cart->update([

            'voucher_id' => $voucher->voucher_id,

            'voucher_code' => $voucher->code,

            'date_updated' => now(),

        ]);



        return $cart->fresh();

    }



    public function remove(WebsiteCart $cart): WebsiteCart

    {

        $cart->update([

            'voucher_id' => null,

            'voucher_code' => null,

            'date_updated' => now(),

        ]);



        return $cart->fresh();

    }



    protected function formatVoucherMeta(WebsiteCart $cart): ?array

    {

        if (empty($cart->voucher_id) && empty($cart->voucher_code)) {

            return null;

        }



        $voucher = $cart->voucher_id

            ? Voucher::find($cart->voucher_id)

            : $this->voucher_service->findByCode($cart->voucher_code, $cart->business_id);



        return $this->formatVoucherFromModel($voucher);

    }



    protected function formatVoucherFromModel(?Voucher $voucher, ?array $preview = null): ?array

    {

        if (!$voucher) {

            return null;

        }



        return [

            'voucher_id' => $voucher->voucher_id,

            'code' => $voucher->code,

            'name' => $voucher->name,

            'rule' => $preview['voucher_rule'] ?? $voucher->describeRule(),

            'discount_amount' => isset($preview['voucher_discount_amount'])

                ? (float) $preview['voucher_discount_amount']

                : null,

        ];

    }

    public function previewOnly(
        WebsiteCart $cart,
        array $items,
        array $context,
        ?string $sale_type_id,
        ?string $voucher_code,
        ?string $voucher_id,
        ?string $payment_method_id = null
    ): array {
        if (empty($items)) {
            throw new Exception('Add items to your cart before applying a voucher.');
        }

        $payload = $this->buildPreviewPayload(
            $cart,
            $items,
            $context,
            $sale_type_id,
            $voucher_code,
            $voucher_id,
            $payment_method_id
        );

        return $this->order_service->previewVoucher($payload);
    }

}

