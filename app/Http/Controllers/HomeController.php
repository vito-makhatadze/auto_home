<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\BrandModel;
use App\Models\Category;
use App\Models\Condition;
use App\Models\Engine;
use App\Models\Page;
use App\Models\Product;
use App\Models\Transmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;

class HomeController extends Controller
{

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $page = Page::where(['status' => true,'slug' => 'home'])->first();
        if (!$page) {
            return abort('404');
        }
        $products = Product::where(['status' => true,'vip' => false])
            ->with(['transmission', 'category', 'condition', 'deal'])
            ->get();
        $vips = Product::where(['status' => true, 'vip' => true])
            ->with(['transmission', 'category', 'condition', 'deal'])
            ->get();

        $brands = Brand::where(['status' => true])->get();
        $transmissions = Transmission::where(['status' => true])->get();
        $conditions = Condition::where(['status' => true])->get();
        return view('frontend.home.index')
            ->with('products', $products)
            ->with('brands', $brands)
            ->with('transmissions', $transmissions)
            ->with('conditions', $conditions)
            ->with('dolar',3.25)
            ->with('page',$page)
            ->with('vips', $vips);
    }

    public function catalog(Request $request)
    {
        $page = Page::where(['status' => true,'slug' => 'catalog'])->first();
        if (!$page) {
            return abort('404');
        }
        if ($request->isMethod('get')) {
            $this->validate($request, [
                'custom' => 'integer',
                'brand' => 'integer',
                'transmission' => 'integer',
                'condition' => 'integer',
                'price_from' => 'integer',
                'price_to' => 'integer',
                'date_from' => 'integer',
                'date_to' => 'integer',
                'engine' => 'integer',
                'category' => 'integer'
            ]);
            $products = Product::where(['status' => true])
                ->with(['transmission', 'category', 'condition', 'deal','engine']);
            if ($request->brand) {
                $products->where(['brand_id' => $request->brand]);
            }
            if ($request->custom) {
                $products->where(['custom' => $request->custom]);
            }
            if ($request->transmission) {
                $products->where(['transmission_id' => $request->transmission]);
            }
            if ($request->condition) {
                $products->where(['condition_id' => $request->condition]);
            }
            if ($request->category) {
                $products->where(['category_id' => $request->category]);
            }
            if ($request->engine) {
                $products->where(['engine_id' => $request->engine]);
            }
            if ($request->price_from) {
                $products->where('price', '>', $request->price_from);
            }
            if ($request->price_to) {
                $products->where('price', '<', $request->price_to);
            }

            if ($request->date_from) {
                $from = $request->date_from . '-01-01 00:00:00';
                $products->where('created_date', '>', $from);
            }

            if ($request->date_to) {
                $to = $request->date_to . '-01-01 00:00:00';
                $products->where('created_date', '<', $to);
            }


            $products = $products->paginate(2);


            $categories = Category::where(['status' => true])->get();
            $transmissions = Transmission::where(['status' => true])->get();
            $engines = Engine::where(['status' => true])->get();
            $conditions = Condition::where(['status' => true])->get();
            $brands = Brand::where(['status' => true])->get();
            $brandModels = [];
            if (isset($brands[0])) {
                $brandModels = BrandModel::where(['status' => true, 'brandmodeleable_type' => 'App\Models\Brand', 'brandmodeleable_id' => $brands[0]->id])->get();
            }

            return view('frontend.catalog.index')
                ->with('products', $products)
                ->with('categories', $categories)
                ->with('transmissions', $transmissions)
                ->with('engines', $engines)
                ->with('conditions', $conditions)
                ->with('brands', $brands)
                ->with('dolar',3.25)
                ->with('page',$page)
                ->with('brandModels', $brandModels);

        }
    }

    public function view(Product $product)
    {
        $page = Page::where(['status' => true,'slug' => 'details'])->first();
        if (!$page) {
            return abort('404');
        }
        $images = $product->image()->get();
        $brand = $product->brand()->get()[0];
        $model = $product->model()->get()[0];
        $deal = $product->deal()->get()[0];
        $engine = $product->engine()->get()[0];
        $engine = $product->engine()->get()[0];

        $news = Product::where(['status' => true])
            ->with(['transmission', 'category', 'condition', 'deal'])
            ->orderBy('created_at', 'desc')->paginate(4);

        $vips = Product::where(['status' => true,'vip' => true])
            ->with(['transmission', 'category', 'condition', 'deal'])
            ->orderBy('created_at', 'desc')->paginate(4);


        return view('frontend.catalog.view')
            ->with('product', $product)
            ->with('brand', $brand)
            ->with('model', $model)
            ->with('deal', $deal)
            ->with('engine', $engine)
            ->with('news', $news)
            ->with('page',$page)
            ->with('vips',$vips)
            ->with('dolar',3.25)
            ->with('images', $images);
    }

    public function about() {
        $page = Page::where(['status' => true,'slug' => 'about-us'])->first();
        if (!$page) {
            return abort('404');
        }
        return view('frontend.about.index')->with('page',$page);

    }

    public function contact() {
        dd(1);
    }

    private function setEnvironmentValue($environmentName, $configKey, $newValue) {
        file_put_contents(App::environmentFilePath(), str_replace(
            $environmentName . '=' . Config::get($configKey),
            $environmentName . '=' . $newValue,
            file_get_contents(App::environmentFilePath())
        ));

        Config::set($configKey, $newValue);

        // Reload the cached config
        if (file_exists(App::getCachedConfigPath())) {
            Artisan::call("config:cache");
        }
    }

    private function getDolar() {
        $client = new SoapClient('http://nbg.gov.ge/currency.wsdl');
        print $client->GetCurrencyDescription('USD').'<br>';
        print $client->GetCurrency('USD').'<br>';
        print $client->GetCurrencyRate('USD').'<br>';
        print $client->GetCurrencyChange('USD').'<br>';
        print $client->GetDate().'<br>';
    }

}
