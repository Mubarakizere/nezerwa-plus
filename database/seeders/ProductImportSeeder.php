<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductImportSeeder extends Seeder
{
    public function run(): void
    {
        $products = $this->getProducts();

        foreach ($products as $item) {
            $categoryName = $this->getCategory($item['name']);
            
            $category = Category::firstOrCreate(
                ['name' => $categoryName],
                ['slug' => Str::slug($categoryName), 'kind' => 'product']
            );

            $product = Product::create([
                'name' => $item['name'],
                'category_id' => $category->id,
                'price' => $item['price'], // Selling price (using cost for now, user can update)
                'cost_price' => $item['price'], // Cost price from list
                'stock' => 0, // Will be set by movement
            ]);

            // Initial Stock Movement
            if ($item['stock'] > 0) {
                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'in',
                    'quantity' => $item['stock'],
                    'unit_cost' => $item['price'],
                    'total_cost' => $item['stock'] * $item['price'],
                    'source_type' => 'App\Models\User', // Attributing initial stock to Admin
                    'source_id' => 1,
                    'user_id' => 1,
                ]);
                
                // Update product stock cache
                $product->stock = $item['stock'];
                $product->save();
            }
        }
    }

    private function getCategory(string $name): string
    {
        $name = strtolower($name);

        if (Str::contains($name, ['beer', 'lager', 'ale', 'stout', 'pilsner', 'heineken', 'corona', 'leffe', 'bavaria', 'skol', 'virunga', 'mutzig', 'amstel', 'turbo', 'primus'])) {
            return 'Beer';
        }
        if (Str::contains($name, ['wine', 'merlot', 'cabernet', 'shiraz', 'chardonnay', 'sauvignon', 'rose', 'blanc', 'rouge', 'red', 'white', 'dry', 'sweet', 'cask', 'cellar', 'chateau', 'maison', 'domaine', 'baron', 'calvet', 'nederburg', 'four cousins', 'robertson', 'saint', 'vino', 'vermouth', 'martini', 'malbec', 'pinot', 'chenin', 'bordeaux'])) {
            return 'Wine';
        }
        if (Str::contains($name, ['champagne', 'sparkling', 'moet', 'chandon', 'veuve', 'clicquot', 'dom perignon', 'brut', 'belaire', 'ruinart', 'mum', 'prosecco'])) {
            return 'Champagne & Sparkling';
        }
        if (Str::contains($name, ['whisky', 'whiskey', 'jameson', 'walker', 'label', 'chivas', 'glen', 'jack', 'beam', 'grant', 'ballantine', 'singleton', 'j&b', 'bond', 'nikka'])) {
            return 'Whisky';
        }
        if (Str::contains($name, ['vodka', 'absolut', 'ciroc', 'grey goose', 'smirnoff', 'belvedere', 'skyy', 'kettel', 'finlandia', 'stoli'])) {
            return 'Vodka';
        }
        if (Str::contains($name, ['gin', 'gordon', 'bombay', 'tanqueray', 'beefeater', 'gilbey', 'hendricks', 'indlovu', 'dagger'])) {
            return 'Gin';
        }
        if (Str::contains($name, ['tequila', 'jose', 'cuervo', 'patron', 'don julio', 'olmeca', 'camino'])) {
            return 'Tequila';
        }
        if (Str::contains($name, ['rum', 'captain', 'morgan', 'bacardi', 'malibu', 'havana'])) {
            return 'Rum';
        }
        if (Str::contains($name, ['brandy', 'cognac', 'hennessy', 'remy', 'martell', 'courvoisier', 'kwv', 'richelieu', 'camus'])) {
            return 'Brandy & Cognac';
        }
        if (Str::contains($name, ['liqueur', 'amarula', 'baileys', 'kahlua', 'cointreau', 'jager', 'campari', 'aperol', 'sheridan', 'tia maria', 'limoncello', 'disaronno', 'grand m', 'wild africa'])) {
            return 'Liqueur';
        }
        if (Str::contains($name, ['water', 'juice', 'soda', 'coke', 'fanta', 'sprite', 'red bull', 'vitalo', 'malt', 'guarana', 'aloe'])) {
            return 'Soft Drinks';
        }

        return 'Uncategorized';
    }

    private function getProducts(): array
    {
        return [
            // Image 0 (Rows 1-37)
            ['name' => 'Vollereaux blanca', 'stock' => 6, 'price' => 85000],
            ['name' => 'G.H mamm', 'stock' => 3, 'price' => 105000],
            ['name' => 'Camus', 'stock' => 7, 'price' => 100893],
            ['name' => 'Grren label', 'stock' => 1, 'price' => 140000],
            ['name' => 'Glenmorangie 18yrs', 'stock' => 4, 'price' => 170000],
            ['name' => 'Singleton 12yrs', 'stock' => 1, 'price' => 49167],
            ['name' => 'Singleton 15yrs', 'stock' => 6, 'price' => 80000],
            ['name' => 'Moet nector', 'stock' => 2, 'price' => 125000],
            ['name' => 'Moet ice', 'stock' => 1, 'price' => 100000],
            ['name' => 'Moet rose', 'stock' => 3, 'price' => 115000],
            ['name' => 'Laurent perier', 'stock' => 6, 'price' => 81000],
            ['name' => 'Vollereaux brut', 'stock' => 7, 'price' => 65000],
            ['name' => 'Veuve', 'stock' => 21, 'price' => 105000],
            ['name' => 'Glenmorangie 12yrs', 'stock' => 6, 'price' => 90000],
            ['name' => 'Glenmorangie 10yrs', 'stock' => 2, 'price' => 70000],
            ['name' => 'Aperol', 'stock' => 11, 'price' => 33167],
            ['name' => 'Campari', 'stock' => 7, 'price' => 41500],
            ['name' => 'Kahlua', 'stock' => 1, 'price' => 42000],
            ['name' => 'Jagermester', 'stock' => 28, 'price' => 39600],
            ['name' => 'Limoncello', 'stock' => 3, 'price' => 32000],
            ['name' => 'Disaronno', 'stock' => 3, 'price' => 62000],
            ['name' => 'Bailey\'s', 'stock' => 6, 'price' => 32000],
            ['name' => 'Tia maria', 'stock' => 7, 'price' => 50000],
            ['name' => 'Sheridon', 'stock' => 3, 'price' => 65000],
            ['name' => 'Grand marnier', 'stock' => 3, 'price' => 42500],
            ['name' => 'Bacardi mojito', 'stock' => 11, 'price' => 27000],
            ['name' => 'Bacardi whit', 'stock' => 5, 'price' => 31000],
            ['name' => 'Captain morgan', 'stock' => 2, 'price' => 27000],
            ['name' => 'Ricard', 'stock' => 2, 'price' => 33000],
            ['name' => 'Malibu', 'stock' => 4, 'price' => 37000],
            ['name' => 'Donjulia resaparadror', 'stock' => 0, 'price' => 137500],
            ['name' => 'Donjulia silver', 'stock' => 7, 'price' => 125000],
            ['name' => 'Patron silver', 'stock' => 3, 'price' => 90000],
            ['name' => 'Patron resaparador', 'stock' => 2, 'price' => 135000],
            ['name' => 'Patron coffee', 'stock' => 6, 'price' => 75000],
            ['name' => 'Nelson', 'stock' => 10, 'price' => 10000],
            ['name' => 'Triple sec', 'stock' => 1, 'price' => 19000],

            // Image 1 (Rows 38-75)
            ['name' => 'Magic moment', 'stock' => 14, 'price' => 15000],
            ['name' => 'Label 5', 'stock' => 1, 'price' => 21000],
            ['name' => 'Southern comfont', 'stock' => 12, 'price' => 27000],
            ['name' => 'Remy martin vsop', 'stock' => 2, 'price' => 130000],
            ['name' => 'Godet XO', 'stock' => 2, 'price' => 184500],
            ['name' => 'Mrtelle XO', 'stock' => 2, 'price' => 450000],
            ['name' => 'HENNESSY XO', 'stock' => 2, 'price' => 507000],
            ['name' => 'MARTEL VSOP', 'stock' => 5, 'price' => 150000],
            ['name' => 'HENNESSY VSOP', 'stock' => 3, 'price' => 110000],
            ['name' => 'HENNESSY VSOP SMALL', 'stock' => 8, 'price' => 125000],
            ['name' => 'HENNESSY VS', 'stock' => 1, 'price' => 85000],
            ['name' => 'Glenfiddich 18yrs', 'stock' => 3, 'price' => 175000],
            ['name' => 'Glenfiddich 15yrs', 'stock' => 1, 'price' => 125000],
            ['name' => 'Glenfiddich 12yrs', 'stock' => 9, 'price' => 114917],
            ['name' => 'Glenlivet reserve', 'stock' => 1, 'price' => 95000],
            ['name' => 'Glenlivet 15yrs', 'stock' => 1, 'price' => 150000],
            ['name' => 'Monkey shoulder', 'stock' => 3, 'price' => 87833],
            ['name' => 'Chivas 15yrs', 'stock' => 1, 'price' => 80000],
            ['name' => 'Chivas 12yrs', 'stock' => 7, 'price' => 45000],
            ['name' => 'Black label', 'stock' => 26, 'price' => 58613],
            ['name' => 'Red label', 'stock' => 12, 'price' => 23750],
            ['name' => 'Grants', 'stock' => 14, 'price' => 30000],
            ['name' => 'Jameson 1L', 'stock' => 2, 'price' => 43500],
            ['name' => 'Jameson black', 'stock' => 10, 'price' => 56000],
            ['name' => 'Ballantine 12yrs', 'stock' => 1, 'price' => 64000],
            ['name' => 'Ballantine', 'stock' => 5, 'price' => 32000],
            ['name' => 'Jim beam', 'stock' => 16, 'price' => 38000],
            ['name' => 'Jack daniel honey', 'stock' => 1, 'price' => 60000],
            ['name' => 'Jack daniel', 'stock' => 1, 'price' => 52000],
            ['name' => 'Olmeca chocolate', 'stock' => 3, 'price' => 35000],
            ['name' => 'Jose cuervo silver', 'stock' => 19, 'price' => 40000],
            ['name' => 'Jose cuervo gold', 'stock' => 32, 'price' => 41000],
            ['name' => 'Camino', 'stock' => 8, 'price' => 22000],
            ['name' => 'Gin bio', 'stock' => 4, 'price' => 52833],
            ['name' => 'Hendricks', 'stock' => 7, 'price' => 89167],
            ['name' => 'Bombay chocolate', 'stock' => 1, 'price' => 20000],
            ['name' => 'Bombay', 'stock' => 2, 'price' => 50000],
            ['name' => 'Belvedere', 'stock' => 1, 'price' => 92500],

            // Image 2 (Rows 76-113)
            ['name' => 'Ciroc', 'stock' => 11, 'price' => 80000],
            ['name' => 'Gilbeys', 'stock' => 47, 'price' => 8600],
            ['name' => 'Gordon gin', 'stock' => 22, 'price' => 21667],
            ['name' => 'Beefeater pink', 'stock' => 1, 'price' => 27000],
            ['name' => 'Tequila rose', 'stock' => 1, 'price' => 45000],
            ['name' => 'Danzka', 'stock' => 10, 'price' => 36900],
            ['name' => 'Gin mare', 'stock' => 1, 'price' => 80000],
            ['name' => 'FINALANDIA', 'stock' => 6, 'price' => 20000],
            ['name' => 'ZAPPA', 'stock' => 10, 'price' => 24000],
            ['name' => 'SKYY F', 'stock' => 8, 'price' => 22000],
            ['name' => 'AMARULA', 'stock' => 11, 'price' => 30250],
            ['name' => 'WILD AFRICA 1L', 'stock' => 6, 'price' => 30000],
            ['name' => 'MOLLY\'S', 'stock' => 6, 'price' => 22000],
            ['name' => 'MARTINI ROSSO', 'stock' => 6, 'price' => 25000],
            ['name' => 'MARTINI BIANCO', 'stock' => 6, 'price' => 26000],
            ['name' => 'MATEUS', 'stock' => 7, 'price' => 22000],
            ['name' => 'ABSOLUT MANDRIN', 'stock' => 0, 'price' => 36500],
            ['name' => 'ABSOLUT VODKA', 'stock' => 2, 'price' => 32000],
            ['name' => 'ABSOLUTE VANILLA', 'stock' => 6, 'price' => 36500],
            ['name' => 'ABSOLUTE MANGO', 'stock' => 3, 'price' => 36500],
            ['name' => 'ABSOLUT RASPBERRI', 'stock' => 1, 'price' => 36500],
            ['name' => 'Chateauneuf du pape', 'stock' => 3, 'price' => 70000],
            ['name' => 'Chateauneuf du pape cuvee', 'stock' => 7, 'price' => 60000],
            ['name' => 'La croix montlabert', 'stock' => 3, 'price' => 55000],
            ['name' => 'Chablis maison castel', 'stock' => 3, 'price' => 50000],
            ['name' => 'Maison louis bourgogne', 'stock' => 1, 'price' => 42000],
            ['name' => 'Chateau ferrande red', 'stock' => 3, 'price' => 40000],
            ['name' => 'Chateau ferrande white', 'stock' => 3, 'price' => 40000],
            ['name' => 'Chateau barreyres', 'stock' => 3, 'price' => 40000],
            ['name' => 'Chateau d arcins', 'stock' => 3, 'price' => 40000],
            ['name' => 'Maison louis macon villages', 'stock' => 3, 'price' => 40000],
            ['name' => 'Maison la tour camblanes', 'stock' => 3, 'price' => 35000],
            ['name' => 'Maison louis brouilly', 'stock' => 3, 'price' => 31000],
            ['name' => 'Maison louis flueurie', 'stock' => 3, 'price' => 31000],
            ['name' => 'Calvet bordeaux', 'stock' => 8, 'price' => 35000],
            ['name' => 'Maison castel jurancon', 'stock' => 3, 'price' => 30000],
            ['name' => 'Chateau tour prignac', 'stock' => 3, 'price' => 30000],
            ['name' => 'Chateau durand laplagne', 'stock' => 8, 'price' => 23000],

            // Image 3 (Rows 114-152)
            ['name' => 'Maison louis beaujulais', 'stock' => 3, 'price' => 26000],
            ['name' => 'Nero maron', 'stock' => 32, 'price' => 24300],
            ['name' => 'Domaine la baume white', 'stock' => 1, 'price' => 25000],
            ['name' => 'Philippe dreschler', 'stock' => 3, 'price' => 25000],
            ['name' => 'Chateau malbec', 'stock' => 3, 'price' => 25000],
            ['name' => 'Medoc maison', 'stock' => 3, 'price' => 25000],
            ['name' => 'Combes saint sauvieur', 'stock' => 3, 'price' => 25000],
            ['name' => 'F. jeantet', 'stock' => 3, 'price' => 25000],
            ['name' => 'Chateau macru', 'stock' => 17, 'price' => 19000],
            ['name' => 'Chateau vignoble', 'stock' => 4, 'price' => 21000],
            ['name' => 'Saumur champigny', 'stock' => 3, 'price' => 24000],
            ['name' => 'Coteaux bourgogne', 'stock' => 3, 'price' => 22000],
            ['name' => 'Plessis duval saumur', 'stock' => 3, 'price' => 22000],
            ['name' => 'Saint nicolas de', 'stock' => 3, 'price' => 22000],
            ['name' => 'Grand verdus red', 'stock' => 4, 'price' => 22000],
            ['name' => 'Grand verdus rose', 'stock' => 7, 'price' => 22000],
            ['name' => 'Cabernet d anjou', 'stock' => 3, 'price' => 21000],
            ['name' => 'Maison castel languedoc', 'stock' => 3, 'price' => 20000],
            ['name' => 'cheteau perron', 'stock' => 6, 'price' => 16000],
            ['name' => 'chateau jillet red', 'stock' => 83, 'price' => 15000],
            ['name' => 'chateau jillet whit', 'stock' => 54, 'price' => 15000],
            ['name' => 'kwv 3yrs', 'stock' => 1, 'price' => 16000],
            ['name' => 'Jacob chardonay sparkling', 'stock' => 12, 'price' => 23000],
            ['name' => 'Grand sud chardonay', 'stock' => 24, 'price' => 16200],
            ['name' => 'Grand sud grenache', 'stock' => 9, 'price' => 16200],
            ['name' => 'Grand sud merlot', 'stock' => 11, 'price' => 16200],
            ['name' => 'Grand sud cabernet', 'stock' => 11, 'price' => 16200],
            ['name' => 'Fenikia rose', 'stock' => 3, 'price' => 16000],
            ['name' => 'kwv merlot', 'stock' => 1, 'price' => 16166],
            ['name' => 'Robertson chardonay', 'stock' => 9, 'price' => 11000],
            ['name' => 'Dagegin', 'stock' => 33, 'price' => 7100],
            ['name' => 'Bazook whisky', 'stock' => 5, 'price' => 5500],
            ['name' => 'amarula small', 'stock' => 1, 'price' => 14500],
            ['name' => 'Robertson rose', 'stock' => 12, 'price' => 11000],
            ['name' => 'Robertson red', 'stock' => 12, 'price' => 11000],
            ['name' => 'pinta negra reserve whit', 'stock' => 2, 'price' => 17000],
            ['name' => 'pinta negra whit dry', 'stock' => 9, 'price' => 11000],
            ['name' => 'pinta negra red dry', 'stock' => 11, 'price' => 11000],

            // Image 4 (Rows 153-190)
            ['name' => 'chevalier red', 'stock' => 12, 'price' => 15000],
            ['name' => 'chevalier whit', 'stock' => 6, 'price' => 15000],
            ['name' => 'chevalier rose', 'stock' => 6, 'price' => 15000],
            ['name' => 'cellar cask red', 'stock' => 4, 'price' => 11800],
            ['name' => 'cellar cask whit', 'stock' => 5, 'price' => 11800],
            ['name' => 'calvet merlot', 'stock' => 1, 'price' => 16000],
            ['name' => 'calvet sauvignon blanc', 'stock' => 0, 'price' => 16000],
            ['name' => 'calvet cabernert', 'stock' => 5, 'price' => 16000],
            ['name' => 'four cousin 1.5 dry whit', 'stock' => 5, 'price' => 19417],
            ['name' => 'four cousin 1.5 sweet whit', 'stock' => 10, 'price' => 20583],
            ['name' => 'four cousin 1.5 red dry', 'stock' => 4, 'price' => 19417],
            ['name' => 'montmeracy red', 'stock' => 25, 'price' => 8100],
            ['name' => 'Robertson rose sweet 750L', 'stock' => 6, 'price' => 8500],
            ['name' => 'Maison castel ICE', 'stock' => 8, 'price' => 18000],
            ['name' => 'villa viron prosecco', 'stock' => 54, 'price' => 15000],
            ['name' => 'baron brut', 'stock' => 13, 'price' => 17000],
            ['name' => 'perlino extra dry', 'stock' => 5, 'price' => 17583],
            ['name' => 'perlino brut', 'stock' => 6, 'price' => 18333],
            ['name' => 'perlino chardonay', 'stock' => 0, 'price' => 13667],
            ['name' => 'nederburg cabernet', 'stock' => 1, 'price' => 17766],
            ['name' => 'canatel prosecco', 'stock' => 7, 'price' => 21000],
            ['name' => 'champgne jillet', 'stock' => 6, 'price' => 20000],
            ['name' => 'baron dem sec', 'stock' => 6, 'price' => 17000],
            ['name' => 'baron ice', 'stock' => 8, 'price' => 16500],
            ['name' => 'drosted small', 'stock' => 24, 'price' => 6000],
            ['name' => 'leffe', 'stock' => 190, 'price' => 2750],
            ['name' => 'corona', 'stock' => 131, 'price' => 2167],
            ['name' => 'stell artos', 'stock' => 40, 'price' => 2083],
            ['name' => 'gilbeys small', 'stock' => 9, 'price' => 2708],
            ['name' => 'Domain white', 'stock' => 9, 'price' => 13000],
            ['name' => 'baron red dry', 'stock' => 28, 'price' => 11000],
            ['name' => 'baron red sweet', 'stock' => 7, 'price' => 11000],
            ['name' => 'baron whit dry', 'stock' => 4, 'price' => 11000],
            ['name' => 'laville pavuron', 'stock' => 3, 'price' => 12000],
            ['name' => 'vill branch red', 'stock' => 7, 'price' => 17000],
            ['name' => 'vill branch whit', 'stock' => 1, 'price' => 17000],
            ['name' => 'beyede merlot', 'stock' => 1, 'price' => 17900],
            ['name' => 'beyede pinotange', 'stock' => 5, 'price' => 17900],

            // Image 9 (Rows 190-226)
            ['name' => 'Royal B rayal', 'stock' => 3, 'price' => 17900],
            ['name' => 'Isabelle sweet whit', 'stock' => 9, 'price' => 8000],
            ['name' => 'Isabelle sweet red', 'stock' => 7, 'price' => 8000],
            ['name' => 'Isabelle dry red', 'stock' => 21, 'price' => 8000],
            ['name' => 'Lamoth red', 'stock' => 7, 'price' => 10000],
            ['name' => 'Lamoth whit', 'stock' => 6, 'price' => 10000],
            ['name' => 'Drosted 75ml whit', 'stock' => 5, 'price' => 8100],
            ['name' => 'Four cousin 5L white', 'stock' => 19, 'price' => 37125],
            ['name' => 'Drosted 5L red', 'stock' => 3, 'price' => 45000],
            ['name' => 'Four cousin 5L red', 'stock' => 27, 'price' => 38125],
            ['name' => 'Baron 5L', 'stock' => 1, 'price' => 37650],
            ['name' => 'Cheteau des anges 2L', 'stock' => 7, 'price' => 17000],
            ['name' => 'Bonne sperance 5L red', 'stock' => 1, 'price' => 30200],
            ['name' => 'Bonne sperance 5L whit', 'stock' => 7, 'price' => 37500],
            ['name' => 'Pinta negra 3L red', 'stock' => 0, 'price' => 28000],
            ['name' => 'Pinta negra 3L whit', 'stock' => 4, 'price' => 28000],
            ['name' => 'Pinta negra 5L red', 'stock' => 3, 'price' => 42500],
            ['name' => 'Robertson 5L Red', 'stock' => 8, 'price' => 32000],
            ['name' => 'La table 5L whit', 'stock' => 1, 'price' => 29000],
            ['name' => 'Cheteau des anges 3L', 'stock' => 2, 'price' => 22100],
            ['name' => 'Imperial blue', 'stock' => 7, 'price' => 8500],
            ['name' => 'Martin extra dry', 'stock' => 1, 'price' => 26000],
            ['name' => 'K. VANT', 'stock' => 2, 'price' => 5583],
            ['name' => 'Bavaria 8.6', 'stock' => 81, 'price' => 2708],
            ['name' => 'Savana', 'stock' => 116, 'price' => 2083],
            ['name' => 'Guarana', 'stock' => 134, 'price' => 1550],
            ['name' => 'Red bull', 'stock' => 31, 'price' => 2000],
            ['name' => 'K.D.B GIN', 'stock' => 4, 'price' => 14000],
            ['name' => 'K.D.B vodka', 'stock' => 4, 'price' => 14000],
            ['name' => 'Royal crescent', 'stock' => 54, 'price' => 7500],
            ['name' => 'Gibson pink', 'stock' => 1, 'price' => 19000],
            ['name' => 'Water inyange', 'stock' => 85, 'price' => 316],
            ['name' => 'Vital', 'stock' => 38, 'price' => 792],
            ['name' => 'Flow', 'stock' => 20, 'price' => 2925],
            ['name' => 'Pinta negra whit sweet', 'stock' => 1, 'price' => 11000],
            ['name' => 'Racky gin', 'stock' => 1, 'price' => 17900],
            ['name' => 'Rayol jubille merlot', 'stock' => 1, 'price' => 17900],

            // Image 10 (Rows 227-264)
            ['name' => 'Lamoth dedium red', 'stock' => 1, 'price' => 10000],
            ['name' => 'Moet brut', 'stock' => 7, 'price' => 100000],
            ['name' => 'Vollereaux rose', 'stock' => 3, 'price' => 70000],
            ['name' => 'Maison castel brut', 'stock' => 2, 'price' => 17000],
            ['name' => 'Vilaviron moscoto', 'stock' => 1, 'price' => 13000],
            ['name' => 'Baron white sweet', 'stock' => 2, 'price' => 11000],
            ['name' => 'Jacobs dry whit', 'stock' => 1, 'price' => 17000],
            ['name' => 'Verglegen savignon blanc', 'stock' => 1, 'price' => 45000],
            ['name' => 'Brancott estate', 'stock' => 1, 'price' => 13000],
            ['name' => 'Calvet chabis', 'stock' => 1, 'price' => 45000],
            ['name' => 'Four cousin sweet whit', 'stock' => 2, 'price' => 12042],
            ['name' => 'Ruinart rose', 'stock' => 1, 'price' => 202000],
            ['name' => 'Nederburg rose', 'stock' => 1, 'price' => 16500],
            ['name' => 'Jacobs sparkling rose', 'stock' => 1, 'price' => 21000],
            ['name' => 'Wild FRICA small', 'stock' => 1, 'price' => 18333],
            ['name' => 'Bellavino rose', 'stock' => 1, 'price' => 14000],
            ['name' => 'Barton& guestier', 'stock' => 1, 'price' => 10000],
            ['name' => 'Vollereaux ice', 'stock' => 1, 'price' => 70000],
            ['name' => 'Donjulia 1942', 'stock' => 2, 'price' => 508333],
            ['name' => 'Domain red', 'stock' => 7, 'price' => 13000],
            ['name' => 'Ruinart blade blanc', 'stock' => 3, 'price' => 150000],
            ['name' => 'Blue label', 'stock' => 1, 'price' => 450000],
            ['name' => 'Baron de rosthschild', 'stock' => 1, 'price' => 200000],
            ['name' => 'Veuve rose', 'stock' => 1, 'price' => 125000],
            ['name' => 'ABK 6 VSOP', 'stock' => 1, 'price' => 80000],
            ['name' => 'MANDERINETTO', 'stock' => 1, 'price' => 5500],
            ['name' => 'ROZZITTA', 'stock' => 1, 'price' => 8500],
            ['name' => 'FANTA', 'stock' => 1, 'price' => 2000],
            ['name' => 'Tanqarayi gin', 'stock' => 6, 'price' => 80000],
            ['name' => 'Tanqarayi 10yrs', 'stock' => 1, 'price' => 105000],
            ['name' => 'Chivas 18yrs', 'stock' => 2, 'price' => 100000],
            ['name' => 'Double blackl', 'stock' => 23, 'price' => 69304],
            ['name' => 'Courvoisier vsop', 'stock' => 2, 'price' => 115000],
            ['name' => 'Beefeater 1L', 'stock' => 9, 'price' => 30000],
            ['name' => 'Macallan 12yrs', 'stock' => 3, 'price' => 149833],
            ['name' => 'Macallan 18yrs', 'stock' => 3, 'price' => 680000],
            ['name' => 'Nedergurg savigna blanca', 'stock' => 12, 'price' => 18450],
            ['name' => 'Nederburg chardonny', 'stock' => 18, 'price' => 17766],

            // Image 11 (Rows 265-270)
            ['name' => 'Famous', 'stock' => 12, 'price' => 23600],
            ['name' => 'Cointreau', 'stock' => 1, 'price' => 55000],
            ['name' => 'Tabaridh', 'stock' => 12, 'price' => 13000],
            ['name' => 'Guinnes', 'stock' => 24, 'price' => 2188],
            ['name' => 'Tusker malt', 'stock' => 24, 'price' => 1874],
            ['name' => 'Tusker lager', 'stock' => 24, 'price' => 1729],
        ];
    }
}
