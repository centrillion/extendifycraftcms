<?php

namespace centrillion\extendifyforcraftcms\queue\jobs;

use Craft;
use craft\queue\BaseJob;
use yii\queue\RetryableJobInterface;
use yii\data\BaseDataProvider;
use SplFileObject;

class FeedDownload extends BaseJob implements RetryableJobInterface
{
    protected $queue;

    public $feedName;

    public $feedUrl;

    public $feedType;

    public $dir;

    public $continueOnError = true;

    public function getTtr() {
        return Craft::$app->getQueue()->ttr;
    }

    public function canRetry($attempt, $error) {
        return Craft::$app->getQueue()->attempts;
    }

    public function execute($queue) {
        $this->queue = $queue;
        try {
            $filename = $this->sanitizeFileName($this->feedName);
            $path = $this->dir . $filename . $this->feedType;

            // remove existing file from the directory, so that youcan redownload the latest feed
            if (!empty($this->feedName) && file_exists($path)) {
                unlink($path);
                Craft::info('Deleted previous feed file ', __METHOD__);
            }

            Craft::info('Downloading Feed from ' . $this->feedUrl, __METHOD__);
            $result = $this->getFeedData($this->feedUrl);

            if (!empty($result)) {
                Craft::info('Starting to save feed data', __METHOD__);
                $this->saveFeedToFile($path, $result);
                Craft::info('Saved Feed = ' . $path, __METHOD__);

                // split the original feed file into multiple files to make it easier to
                // process it later.
                if($this->feedType == '.csv') {
                    Craft::info('Started Splitting File in to Chunks', __METHOD__);
                    $this->splitFileInChunks($path,'-split-');
                }

            }

        } catch (\Throwable $e) {
            // Even though we catch errors on each step of the loop, make sure to catch errors that can be anywhere
            // else in this function, just to be super-safe and not cause the queue job to die.
            // Plugin::error('`{e} - {f}: {l}`.', ['e' => $e->getMessage(), 'f' => basename($e->getFile()), 'l' => $e->getLine()]);
            Craft::$app->getErrorHandler()->logException($e);
        }
    }

    private function sanitizeFileName($name) {
        return preg_replace('/[^a-z0-9]+/', '-', strtolower( $name ));
    }

    private function getFeedData($feedUrl) {
        
        $this->setProgress($this->queue, 0, 'Start downloading feed');

        //  Initiate curl
        $ch = curl_init();
        // Disable SSL verification
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // Will return the response, if false it print the response
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Set the url
        curl_setopt($ch, CURLOPT_URL,$feedUrl);
        
        // Check HTTP status code
        if (!curl_errno($ch)) {
            // Execute
            $result = curl_exec($ch);
            // Closing
            curl_close($ch);

            return $result;   
        }
	}

    private function saveFeedToFile($path, $feedContent) {

        $this->setProgress($this->queue, 0, 'Saving downloaded feed data');
        return file_put_contents($path, $feedContent);
    }

    private function readFeedFromFile($path, $dirPath) {

      if (!empty($path) && file_exists($path)) {
        // get content from file and return the json objects.
        $this->setProgress($this->queue, 0, 'Data feed exists');
        return file_get_contents($path);
      }
      return null;
	}

    private function splitFileInChunks($path, $pathPrefix, $numberOfLines = 5000) {
        $file = new SplFileObject($path);

        //get header of the csv
        $header = $file->fgets();

        $outputBuffer = '';
        $parts = explode(".", $path);

        $readLinesCount = 1;
        $readlLinesTotalCount = 1;
        $suffix=0;

        $outputBuffer .= $header;

        $this->setProgress($this->queue, 0, 'Start downloading feed');

        while ($currentLine = $file->fgets()) {
            $outputBuffer .= $currentLine;
            $readLinesCount++;
            $readlLinesTotalCount++;

            if ($readLinesCount >= $numberOfLines) {

                $outputFilename = $parts[0] . $pathPrefix . $suffix . '.' . $parts[1];
                file_put_contents($outputFilename, $outputBuffer);
                Craft::info('Created Partial File = ' . $outputFilename, __METHOD__);
                //echo 'Wrote '  . $readLinesCount . ' lines to: ' . $outputFilename . PHP_EOL;   
                $this->setProgress($this->queue, 0, 'Splitting data to small chunks'); 

                $outputBuffer = $header;
                $readLinesCount = 0;
                $suffix++;
            }
        }

        //write remainings of output buffer if it is not empty
        if ($outputBuffer !== $header) {
            $outputFilename = $parts[0] . $pathPrefix . $suffix . '.' . $parts[1];
            file_put_contents($outputFilename, $outputBuffer);
            //echo 'Wrote (last time)'  . $readLinesCount . ' lines to: ' . $outputFilename . PHP_EOL;
            Craft::info('Created Last Partial File = ' . $outputFilename, __METHOD__);
            $this->setProgress($this->queue, 0, 'Splitting data to small chunks'); 

            $outputBuffer = '';
            $readLinesCount = 0;
        }

        $this->setProgress($this->queue, 1, 'Completed Spliting Feed Data');
    }

    private function splitFileInChunksX() {
    
      $file_src = $path;
      $file_name = str_replace(".csv","",$_FILES['source_file']['name']);
      $file_counter = 1; // append to end of file name

      $i = 0; // source file row counter
      $col = 0; // source file row counter
      $row = 1; // destination file counter (keep under $max_rows)
      
      if(($handle = fopen($file_src, "r")) !== FALSE) {

        while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
          $col = count($data);
          //echo "<pre>".print_r($data,1)."</pre>";
          if($i==0){
            
            // store the file header
            for($n=0;$n<$col;$n++){
              if($n>0){
                $file_header.= ",";
              }
              
              $file_header.= $data[$n];
            }
            
            $file_header.= "\n";
          }
          else{
            if($row<$max_rows){
              for($n=0;$n<$col;$n++){
                if($n>0){
                  $file_content.= ",";
                }
              
                $file_content.= '"'.$data[$n].'"';
              }
              
              $file_content.= "\n";
            }
            else{
              $this->makeFile($file_name,$file_counter,$split_dir,$file_header,$file_content);
              
              // increment
              $file_counter++;
              
              // reset
              $file_content = "";
              
              // record this row
              for($n=0;$n<$col;$n++){
                if($n>0){
                  $file_content.= ",";
                }
              
                $file_content.= '"'.$data[$n].'"';
              }
              
              $file_content.= "\n";
              
              
              $row = 1;
            }
            $row++;
          }
          $i++;
        }
        
        $this->makeFile($file_name,$file_counter,$split_dir,$file_header,$file_content);
        
        fclose($handle);
      }
    }

    private function makeFile($file_name,$file_counter,$split_dir,$file_header,$file_content){
        // name file
        $name = $file_name."_".$file_counter.".csv";
        
        // set path
        $path = $split_dir.$name;
        
        // set content
        $content = $file_header.$file_content;
        
        // save file
        if(($fp = fopen($path, "w+")) !== FALSE) {
        fwrite($fp, $content);
        fclose($fp);
        }
        
    }

    // Protected Methods
    // =========================================================================
    protected function defaultDescription(): string {
        return 'Downloading Feed ' . $this->feedName;
    }
}