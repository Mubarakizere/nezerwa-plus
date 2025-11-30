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
                    'remarks' => 'Initial Import',
                    'date' => now(),
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
            // Image 1
            ['name' => 'Vino Dte/schlr', 'stock' => 1, 'price' => 0], // Price missing?
            ['name' => 'Chevalier de Lasalle Rose', 'stock' => 1, 'price' => 0], // Price missing?
            ['name' => 'Pinta Reserve White', 'stock' => 1, 'price' => 0], // Price missing?
            ['name' => 'Volleaurent Blanc de Blanc', 'stock' => 5, 'price' => 85000],
            ['name' => 'GH Mum', 'stock' => 3, 'price' => 105000],
            ['name' => 'Camus VS', 'stock' => 7, 'price' => 100893],
            ['name' => 'Glen Label', 'stock' => 1, 'price' => 140000],
            ['name' => 'Glenmorangie 18yrs', 'stock' => 4, 'price' => 170000],
            ['name' => 'Moet Nectar', 'stock' => 2, 'price' => 125000],
            ['name' => 'Moet Ice', 'stock' => 1, 'price' => 100000],
            ['name' => 'Volleaurent Brut Reserve', 'stock' => 7, 'price' => 65000],
            ['name' => 'Moet Rose', 'stock' => 3, 'price' => 115000],
            ['name' => 'Volleaurent Ice', 'stock' => 2, 'price' => 70000],
            ['name' => 'Laurent Perrier Brut', 'stock' => 6, 'price' => 81000],
            ['name' => 'Veuve Clicquot', 'stock' => 3, 'price' => 100000],
            ['name' => 'Singleton 12yrs', 'stock' => 1, 'price' => 49166],
            ['name' => 'Singleton 15yrs', 'stock' => 6, 'price' => 80000],
            ['name' => 'Glenmorangie 12yrs', 'stock' => 6, 'price' => 90000],
            ['name' => 'Glenmorangie 10yrs', 'stock' => 3, 'price' => 70000],
            ['name' => 'Aperol', 'stock' => 6, 'price' => 33166],
            ['name' => 'Campari', 'stock' => 1, 'price' => 4333], // 43,33? or 43333? Image says 43,33. Likely 43,333
            ['name' => 'Kahlua', 'stock' => 1, 'price' => 42000],
            ['name' => 'Jagermeister', 'stock' => 28, 'price' => 39600],
            ['name' => 'Limoncello', 'stock' => 3, 'price' => 32000],
            ['name' => 'Disaronno', 'stock' => 3, 'price' => 68000],
            ['name' => 'Sheridan', 'stock' => 3, 'price' => 60000],
            ['name' => 'Cointreau', 'stock' => 1, 'price' => 55000],
            ['name' => 'Grand Marnier', 'stock' => 3, 'price' => 0], // Price missing?
            ['name' => 'Bacardi Mojito', 'stock' => 11, 'price' => 27000],

            // Image 2
            ['name' => 'Grants', 'stock' => 14, 'price' => 30583],
            ['name' => 'Jameson Black', 'stock' => 5, 'price' => 56000],
            ['name' => 'Jameson Irish', 'stock' => 3, 'price' => 44000],
            ['name' => 'Ballantines 12yo', 'stock' => 1, 'price' => 60000],
            ['name' => 'Ballantines', 'stock' => 5, 'price' => 32000],
            ['name' => 'Jim Beam', 'stock' => 16, 'price' => 38000],
            ['name' => 'Jack Daniels Honey', 'stock' => 1, 'price' => 58000],
            ['name' => 'Jack Daniels', 'stock' => 1, 'price' => 52000],
            ['name' => 'Olmeca Chocolate', 'stock' => 3, 'price' => 36000],
            ['name' => 'Jose Cuervo Gold', 'stock' => 1, 'price' => 38500],
            ['name' => 'Jose Cuervo Silver', 'stock' => 27, 'price' => 40000],
            ['name' => 'Camino', 'stock' => 10, 'price' => 30000],
            ['name' => 'Gin Bio', 'stock' => 4, 'price' => 52833],
            ['name' => 'Bombay', 'stock' => 1, 'price' => 20000],
            ['name' => 'Belvedere', 'stock' => 1, 'price' => 92500],
            ['name' => 'Bombay Sapphire', 'stock' => 2, 'price' => 55000],
            ['name' => 'Ciroc', 'stock' => 11, 'price' => 80000],
            ['name' => 'Gilbeys', 'stock' => 51, 'price' => 8600],
            ['name' => 'Gordons Gin', 'stock' => 44, 'price' => 21666],
            ['name' => 'Beefeater', 'stock' => 5, 'price' => 30000],
            ['name' => 'Beefeater Pink', 'stock' => 1, 'price' => 25500],
            ['name' => 'Tequila Rose', 'stock' => 1, 'price' => 44000],
            ['name' => 'Danzka', 'stock' => 10, 'price' => 36900],
            ['name' => 'Stoli Vodka', 'stock' => 5, 'price' => 20000],
            ['name' => 'Finlandia', 'stock' => 6, 'price' => 20000],
            ['name' => 'Gin Mare', 'stock' => 1, 'price' => 80000],
            ['name' => 'Courvoisier VSOP', 'stock' => 2, 'price' => 115000],
            ['name' => 'Zappa', 'stock' => 10, 'price' => 24000],
            ['name' => 'Skyy Vodka', 'stock' => 8, 'price' => 22000],
            ['name' => 'Martini Bianco', 'stock' => 3, 'price' => 26000],
            ['name' => 'Baileys', 'stock' => 13, 'price' => 29166],

            // Image 3
            ['name' => 'Amarula Small', 'stock' => 1, 'price' => 14500],
            ['name' => 'Pinta Reserve Red', 'stock' => 1, 'price' => 16500],
            ['name' => 'Pinta Reserve White', 'stock' => 2, 'price' => 16500],
            ['name' => 'Pinta White Wine', 'stock' => 22, 'price' => 11000],
            ['name' => 'Chevalier de Lasalle', 'stock' => 24, 'price' => 15000],
            ['name' => 'Four Cousins Wine', 'stock' => 6, 'price' => 12042],
            ['name' => 'Cellar Cask Wine', 'stock' => 9, 'price' => 11800],
            ['name' => 'Nederburg Red', 'stock' => 5, 'price' => 18500],
            ['name' => 'Calvet Wine', 'stock' => 9, 'price' => 16000],
            ['name' => 'Four Cousins 1.5L', 'stock' => 10, 'price' => 19416],
            ['name' => 'Four Cousins 1.5L Sweet', 'stock' => 12, 'price' => 20583],
            ['name' => 'Mont Ymerac Wine', 'stock' => 26, 'price' => 8100],
            ['name' => 'Robertson', 'stock' => 7, 'price' => 8500],
            ['name' => 'Maison Castel Demisec', 'stock' => 8, 'price' => 19000],
            ['name' => 'Villaveroni', 'stock' => 76, 'price' => 15000],
            ['name' => 'Baron Sparkling', 'stock' => 28, 'price' => 17000],
            ['name' => 'Muscador Rose', 'stock' => 1, 'price' => 13000],
            ['name' => 'Perlino Extra Dry', 'stock' => 5, 'price' => 17583],
            ['name' => 'Perlino Brut', 'stock' => 6, 'price' => 18333],
            ['name' => 'Perlino Chardonnay', 'stock' => 5, 'price' => 13666],
            ['name' => 'Canetelli', 'stock' => 7, 'price' => 21000],
            ['name' => 'Baron Ice', 'stock' => 8, 'price' => 16500],
            ['name' => 'Domaine Wine', 'stock' => 17, 'price' => 13000],
            ['name' => 'Gilbeys Small', 'stock' => 21, 'price' => 2708],
            ['name' => 'Baron Wine', 'stock' => 45, 'price' => 10500],
            ['name' => 'Laville Pavillon', 'stock' => 3, 'price' => 12000],

            // Image 4
            ['name' => 'Bacardi White', 'stock' => 11, 'price' => 31000],
            ['name' => 'Captain Morgan', 'stock' => 7, 'price' => 27000],
            ['name' => 'Ricard', 'stock' => 3, 'price' => 33000],
            ['name' => 'Malibu', 'stock' => 4, 'price' => 37000],
            ['name' => 'Don Julio Silver', 'stock' => 7, 'price' => 125000],
            ['name' => 'Patron Silver', 'stock' => 7, 'price' => 90000],
            ['name' => 'Don Julio Gold', 'stock' => 7, 'price' => 137000],
            ['name' => 'Patron Gold', 'stock' => 2, 'price' => 135000],
            ['name' => 'Patron Coffee', 'stock' => 6, 'price' => 75000],
            ['name' => 'Nelson', 'stock' => 11, 'price' => 10000],
            ['name' => 'Triple Sec', 'stock' => 1, 'price' => 20000],
            ['name' => 'Label 5', 'stock' => 1, 'price' => 22000],
            ['name' => 'William Lawson', 'stock' => 3, 'price' => 15000],
            ['name' => 'Southern Comfort', 'stock' => 12, 'price' => 28000],
            ['name' => 'Remy Martin', 'stock' => 1, 'price' => 90000],
            ['name' => 'Godet XO', 'stock' => 2, 'price' => 184500],
            ['name' => 'Martell XO', 'stock' => 2, 'price' => 450000],
            ['name' => 'Hennessy XO', 'stock' => 2, 'price' => 507000],
            ['name' => 'Martell VSOP', 'stock' => 1, 'price' => 125000],
            ['name' => 'Hennessy VSOP', 'stock' => 8, 'price' => 121500],
            ['name' => 'Hennessy VS', 'stock' => 3, 'price' => 85000],
            ['name' => 'Glenfiddich 18yrs', 'stock' => 3, 'price' => 170000],
            ['name' => 'Glenfiddich 12yrs', 'stock' => 9, 'price' => 115000],
            ['name' => 'Glenfiddich 15yrs', 'stock' => 1, 'price' => 125000],
            ['name' => 'Glenlivet Reserve', 'stock' => 1, 'price' => 95000],
            ['name' => 'Glenlivet 15yo', 'stock' => 1, 'price' => 150000],
            ['name' => 'Monkey Shoulder', 'stock' => 3, 'price' => 87900],
            ['name' => 'Chivas 15yo', 'stock' => 1, 'price' => 80000],
            ['name' => 'Chivas 18yo', 'stock' => 1, 'price' => 130000],
            ['name' => 'Black Label', 'stock' => 2, 'price' => 61666],
            ['name' => 'Red Label', 'stock' => 6, 'price' => 23750],

            // Image 5
            ['name' => 'Mollys', 'stock' => 6, 'price' => 22000],
            ['name' => 'Tia Maria', 'stock' => 7, 'price' => 50000],
            ['name' => 'Amarula', 'stock' => 26, 'price' => 30250],
            ['name' => 'Wild Africa 1L', 'stock' => 6, 'price' => 30000],
            ['name' => 'Wild Africa 75cl', 'stock' => 3, 'price' => 18333],
            ['name' => 'Absolut Mango', 'stock' => 5, 'price' => 36500],
            ['name' => 'Absolut Mandrin', 'stock' => 3, 'price' => 36500],
            ['name' => 'Absolut Vodka', 'stock' => 4, 'price' => 32000],
            ['name' => 'Absolut Raspberry', 'stock' => 1, 'price' => 34500],
            ['name' => 'Absolut Vanilla', 'stock' => 6, 'price' => 36500],
            ['name' => 'Chateau du Pape', 'stock' => 8, 'price' => 60000],
            ['name' => 'Maison Chateau du Pape', 'stock' => 3, 'price' => 70000],
            ['name' => 'La Croix', 'stock' => 3, 'price' => 55000],
            ['name' => 'Maison Chablis', 'stock' => 3, 'price' => 50000],
            ['name' => 'Maison Bourgogne', 'stock' => 3, 'price' => 42000],
            ['name' => 'Chateau Ferrande Red', 'stock' => 3, 'price' => 40000],
            ['name' => 'Chateau Ferrande White', 'stock' => 3, 'price' => 40000],
            ['name' => 'Chateau Barreyres', 'stock' => 3, 'price' => 40000],
            ['name' => 'Chateau Darcins', 'stock' => 3, 'price' => 40000],
            ['name' => 'Maison Macon-Villages', 'stock' => 3, 'price' => 40000],
            ['name' => 'Chateau Latour Camblanes', 'stock' => 3, 'price' => 35000],
            ['name' => 'Maison Brouilly', 'stock' => 3, 'price' => 31000],
            ['name' => 'Maison Fleurie', 'stock' => 3, 'price' => 31000],
            ['name' => 'Calvet Bordeaux', 'stock' => 8, 'price' => 35000],
            ['name' => 'Maison Jurancon', 'stock' => 3, 'price' => 30000],
            ['name' => 'Chateau Tour Prignac', 'stock' => 3, 'price' => 30000],
            ['name' => 'Durand Laplagne', 'stock' => 8, 'price' => 23000],
            ['name' => 'Maison Beaujolais Villages', 'stock' => 3, 'price' => 26000],
            ['name' => 'Domaine de la Baume', 'stock' => 1, 'price' => 25000],
            ['name' => 'Nero Marone', 'stock' => 72, 'price' => 24300],

            // Image 6
            ['name' => 'Villaveroni Rose Brut', 'stock' => 3, 'price' => 17000],
            ['name' => 'Maison Castel Brut', 'stock' => 3, 'price' => 85000],
            ['name' => 'Maison Castel Rose Brut', 'stock' => 1, 'price' => 18000],
            ['name' => 'Ruinart Rose', 'stock' => 1, 'price' => 202000],
            ['name' => 'Ruinart Blanc', 'stock' => 1, 'price' => 150000],
            ['name' => 'Bella Viron Rose', 'stock' => 1, 'price' => 14000],
            ['name' => 'Robertson Rose Sparkling', 'stock' => 1, 'price' => 21000],
            ['name' => 'Barton & Guestier', 'stock' => 1, 'price' => 10000],
            ['name' => 'Valdepablo', 'stock' => 1, 'price' => 12000],
            ['name' => 'Nederburg Rose', 'stock' => 1, 'price' => 16500],
            ['name' => 'Jacobs Wine', 'stock' => 1, 'price' => 16000],
            ['name' => 'Grand Verdos Rose', 'stock' => 7, 'price' => 18000],
            ['name' => 'Brancott', 'stock' => 1, 'price' => 13000],
            ['name' => 'Vergelegen Savignon', 'stock' => 1, 'price' => 45000],
            ['name' => 'Calvet Chablis', 'stock' => 1, 'price' => 45000],
            ['name' => 'Villaveroni Muscatto', 'stock' => 1, 'price' => 13000],

            // Image 7
            ['name' => 'Maison Philippe Dreschler', 'stock' => 3, 'price' => 25000],
            ['name' => 'Malbec Maison', 'stock' => 3, 'price' => 25000],
            ['name' => 'Maison Medoc', 'stock' => 3, 'price' => 25000],
            ['name' => 'Combes Saint Sauveur', 'stock' => 3, 'price' => 25000],
            ['name' => 'F. Jeantet', 'stock' => 3, 'price' => 25000],
            ['name' => 'Chateau Macon', 'stock' => 18, 'price' => 19000],
            ['name' => 'Chateau Vignoble', 'stock' => 6, 'price' => 21000],
            ['name' => 'Chateau Laubes', 'stock' => 3, 'price' => 32000],
            ['name' => 'Saumur Champigny', 'stock' => 3, 'price' => 24000],
            ['name' => 'Maison Coteaux', 'stock' => 3, 'price' => 50000],
            ['name' => 'Plessis-Duval', 'stock' => 3, 'price' => 22000],
            ['name' => 'Saint-Nicolas de Bourgueil', 'stock' => 3, 'price' => 22000],
            ['name' => 'Grand Verdos Red', 'stock' => 5, 'price' => 22000],
            ['name' => 'Grand Verdos White', 'stock' => 1, 'price' => 22000],
            ['name' => 'Cabernet d\'Anjou', 'stock' => 3, 'price' => 21000],
            ['name' => 'Maison Languedoc', 'stock' => 3, 'price' => 20000],
            ['name' => 'Chateau Perron', 'stock' => 6, 'price' => 16000],
            ['name' => 'Chateau Gillet Red', 'stock' => 23, 'price' => 15000],
            ['name' => 'Chateau Gillet White', 'stock' => 2, 'price' => 15000],
            ['name' => 'KWV 3yrs', 'stock' => 1, 'price' => 18000],
            ['name' => 'Jacobs Brut', 'stock' => 12, 'price' => 21000],
            ['name' => 'Grand Sud Chardonnay', 'stock' => 24, 'price' => 16200],
            ['name' => 'Grand Sud Grenache', 'stock' => 9, 'price' => 16200],
            ['name' => 'Cabernet Merlot', 'stock' => 23, 'price' => 16200],
            ['name' => 'Fenicia Rose', 'stock' => 3, 'price' => 16000],
            ['name' => 'KWV Wine', 'stock' => 1, 'price' => 15000],
            ['name' => 'Dagger Gin', 'stock' => 34, 'price' => 7700],
            ['name' => 'Bazzoka', 'stock' => 5, 'price' => 5833],

            // Image 8
            ['name' => 'KDB', 'stock' => 8, 'price' => 14000],
            ['name' => 'Blue Curacao', 'stock' => 1, 'price' => 15666],
            ['name' => 'Magic Moment', 'stock' => 2, 'price' => 15000],
            ['name' => 'Gibson Pink', 'stock' => 1, 'price' => 19000],
            ['name' => 'ABK6 VSOP', 'stock' => 1, 'price' => 80000],
            ['name' => 'Rack Gin', 'stock' => 1, 'price' => 5500],
            ['name' => 'Rozzita', 'stock' => 1, 'price' => 4500],
            ['name' => 'Martini Extra Dry', 'stock' => 1, 'price' => 5500],
            ['name' => 'Mandarinetto', 'stock' => 1, 'price' => 5500],
            ['name' => 'Kuanha', 'stock' => 2, 'price' => 5583],
            ['name' => 'Imperial Blue', 'stock' => 7, 'price' => 8500],
            ['name' => 'Mateus', 'stock' => 1, 'price' => 22000],
            ['name' => 'Royal Crescent', 'stock' => 61, 'price' => 7500],
            ['name' => 'Water (Amazi)', 'stock' => 11, 'price' => 316],
            ['name' => 'Leffe', 'stock' => 244, 'price' => 2750],
            ['name' => 'Corona', 'stock' => 181, 'price' => 2208],
            ['name' => 'Stella Artois', 'stock' => 210, 'price' => 2083],
            ['name' => 'Guarana', 'stock' => 262, 'price' => 1550],
            ['name' => 'Bavaria 8.6', 'stock' => 86, 'price' => 2708],
            ['name' => 'Savanna', 'stock' => 61, 'price' => 2083],
            ['name' => 'Red Bull', 'stock' => 91, 'price' => 2000],
            ['name' => 'Aloe Vera', 'stock' => 21, 'price' => 2925],
            ['name' => 'Vitalo', 'stock' => 38, 'price' => 791],
            ['name' => 'Fanta', 'stock' => 6, 'price' => 2000],
        ];
    }
}
