<?php
// Compile Blade to temporary PHP, lint, then delete only these temporary files.
require __DIR__.'/../vendor/autoload.php';
$app = new Illuminate\Foundation\Application(realpath(__DIR__.'/..'));
$finder = new Illuminate\View\FileViewFinder(new Illuminate\Filesystem\Filesystem, [__DIR__.'/../resources/views']);
$factory = new Illuminate\View\Factory(new Illuminate\View\Engines\EngineResolver, $finder, new Illuminate\Events\Dispatcher($app));
$app->instance('view', $factory);
$app->instance(Illuminate\Contracts\View\Factory::class, $factory);
$compiler=new Illuminate\View\Compilers\BladeCompiler(new Illuminate\Filesystem\Filesystem,sys_get_temp_dir());
$files=glob(__DIR__.'/../resources/views/ict-inventory/*.blade.php');
$files[]=__DIR__.'/../resources/views/institutions/show.blade.php';
foreach($files as $path){
 $compiled=$compiler->compileString(file_get_contents($path));
 $temp=tempnam(sys_get_temp_dir(),'talastas-view-');
 try {
  file_put_contents($temp,$compiled);
  $pipes=[];$process=proc_open([PHP_BINARY,'-l',$temp],[1=>['pipe','w'],2=>['pipe','w']],$pipes);
  $output=stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
  if(proc_close($process)!==0)throw new RuntimeException(basename($path).': '.$output);
 } finally {unlink($temp);}
}
echo count($files)." Blade templates compiled and passed PHP syntax checks.\n";
