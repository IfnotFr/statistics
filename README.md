# Statistics

Allows you to extract a `Illuminate\Support\Collection` of statistics about a set of dated `Eloquent` rows.

## Features
  * **Based on Carbon** : for easy date management and for an easy integration with `Eloquent`
  * **A simple Cache** : for avoiding useless dupliated database queries
  * **Custom interval and steps** : allows you to change dates (start, end) and the steps (yearly, monthly, daily, hourly)
  * **Return a Collection** : a `Illuminate\Support\Collection` is returned for a simpler statistics handling
  * **Indicator based** : for a simple and comprehensive way of querying statistics datas

## Installation

Install it with composer :

   composer require white-frame/statistics:5.1

**Use the release corresponding to your laravel version : 5.1 for Laravel 5.1**

## Usage

### 1 : Instanciate a `Statistics` object from a eloquent query :

```php
use WhiteFrame\Statistics\Statistics;

// Get all validated sales (you can also take all with Sale::query())
$validSales = Sale::whereNotNull('validated_at');

// Put our sales into a Statistics object
$statistics = Statistics::of($validSales);
```

### 2 : Specify the date column and the interval for filtering datas :

```php
use WhiteFrame\Statistics\Interval;

// If we want to build statistics about validation date of sales (it can be also of the creation date, 
// in this case we will use the eloquent created_at ...) 
$statistics->date('validated_at');

// Set the interval, the params :
// 1 : the interval, check the constants Interval::$DAILY, Interval::$MONTHLY etc ... or use the string "daily", "monthly" instead
// 2 : the start date with carbon
// 3 : the end date with carbon
$statistics->interval(Interval::$DAILY, Carbon::createFromFormat('Y-m-d', '2016-01-01'), Carbon::now())
```

### 3 : Add indicators :

```php
// We want to count the sales with shipping and without shipping
$statistics->indicator('with_shipping', function($row) {
 return $row->shipping ? 1 : 0;
});
$statistics->indicator('without_shipping', function($row) {
 return $row->shipping ? 0 : 1;
});

// And count the sales with more than 500.00 € amount
$statistics->indicator('expensive_bough', function($row) {
 if($row->amount > 500.00) {
  return 1;
 } else {
  return 0;
 }
});
```

### 4 : Handle datas :
```php
$collection = $statistics->make();

// Use a foreach if you want to loop on each dates
foreach($collection as $date => $values) {
 echo $date ' : ' . $values->expensive_bough;
}

// Use Collection methods for statistics
$collection->sum('with_shipping'); // Count the shipping
$collection->avg('with_shipping'); // Average shpping sales on each days on the interval (if you selected daily)
$collection->min('with_shipping'); // Minimum daily shipping on the interval
$collection->max('with_shipping');

// etc ...
```

## Full Documentation

... Work here
