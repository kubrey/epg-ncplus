# EPG parser for ncplus.pl

### Usage

```
require_once('vendor/autoload.php');

use ncplus/EpgParser;

$parser = new EpgParser();
// to get all channels programs for day
$data = $parser->loadDay(date('Y-m-d');
if($data){
    $programs = $parser->parseCommonData($data);
}
```

`$programs` is an array with 2 keys: `channels` and `programs`

`$programs['channels']` is an array where keys are channel's id and value is channel's name

`$programs['programs']` is multidimensional array of programs for each channel


E.g.:

```
[961]=>
    array(10) {
      [0]=>
      array(6) {
        ["id"]=>
        int(22084262)
        ["name"]=>
        string(6) "Tuvalu"
        ["airDate"]=>
        string(10) "2016-05-05"
        ["airTime"]=>
        string(8) "08:25:00"
        ["airLength"]=>
        int(5400)
        ["idChannel"]=>
        int(961)
      }
      [1]=>
      array(6) {
        ["id"]=>
        int(22084263)
        ["name"]=>
        string(6) "Idiota"
        ["airDate"]=>
        string(10) "2016-05-05"
        ["airTime"]=>
        string(8) "09:55:00"
        ["airLength"]=>
        int(10200)
        ["idChannel"]=>
        int(961)
      }
      ...

```

In this example `961` is channel id

To get information on program:

```
$program = $parser->getProgramInfo($id);//$id - program's id from $programs['programs']
if($program){
    $parsed = $parser->parseProgramData($program);
}
```

This should return something like this

```
array(6) {
  ["descr"]=>
  string(269) "Anton marzy, by uciec na wyspę zwaną Tuvalu. Wszystko wskazuje jednak na to, że jego marzenie raczej się nie spełni biorąc pod uwagę, że Anton pracuje jako konserwator mało popularnego basenu należącego do jego niewidomego ojca. Bohater za wszelką cenę ..."
  ["urlNcpluspl"]=>
  string(45) "2710953-tuvalu-filmbox-arthouse-20160505-0725"
  ["category"]=>
  NULL
  ["country"]=>
  string(6) "Niemcy"
  ["movieCast"]=>
  string(98) "Denis Lavant, Chulpan Khamatova, Philippe Clay, Terrence Gillespie, Catalina Murgea, E.J. Callahan"
  ["movieDirector"]=>
  string(11) "Veit Helmer"
}

```
