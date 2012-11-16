<?php
namespace Barberry\Filter;
use Barberry;
use Barberry\ContentType;

include_once dirname(dirname(dirname(__DIR__))) . '/externals/Tbs/tbs_class.php';
include_once dirname(dirname(dirname(__DIR__))) . '/externals/Tbs/tbs_plugin_opentbs.php';

class OpenOfficeTemplate implements FilterInterface {

    /**
     * @var \clsTinyButStrong
     */
    private $tbs;

    /**
     * @var string
     */
    private $tempPath;

    public function __construct($tempPath, \clsTinyButStrong $tbs = null) {
        $this->tempPath = $tempPath;
        $this->tbs = $tbs;

        if (is_null($this->tbs)) {
            $this->tbs = new \clsTinyButStrong;
        }

        $this->tbs->SetOption('noerr', true);
    }

    /**
     * @param \Barberry\PostedFile\Collection $allFiles
     * @param array $vars
     * @return void
     */
    public function filter(\Barberry\PostedFile\Collection $allFiles, array $vars) {
        $allFiles->rewind();
        $fileKey = $allFiles->key();
        $file = $allFiles->current();

        $vars = $this->getVars($allFiles, $vars);

        if (is_null($file) || !strlen($file->bin) || !$this->isContentTypeSupported($file->bin)) {
            return;
        }
        if (empty($vars) && $this->noOtherFiles($allFiles, $fileKey)) {
            return;
        }

        $this->tbs->PlugIn(TBS_INSTALL, OPENTBS_PLUGIN);
        $tempFileName = $this->toTempFileWithExt($file);
        $this->tbs->LoadTemplate($tempFileName, OPENTBS_ALREADY_UTF8);

        $tempFileNames = $this->savePostedFilesWithExtensions($allFiles, $fileKey);
        $vars += $tempFileNames;

        $this->tbs->VarRef = array('res' => $vars);

        foreach ($vars as $key => $val) {
            if (is_array($val)) {
                $this->tbs->MergeBlock($key, isset($val[0]) ? $val : array());
            } else {
                $this->tbs->MergeField($key, $val);
            }
        }

        $this->tbs->Show(OPENTBS_STRING);

        foreach ($tempFileNames as $filename) {
            unlink($filename);
        }
        unlink($tempFileName);

        $allFiles[$fileKey] = new \Barberry\PostedFile($this->tbs->Source, $file->filename);
    }

//--------------------------------------------------------------------------------------------------

    /**
     * @param \Barberry\PostedFile\Collection $files
     * @param string $skipName
     * @return array
     */
    private function savePostedFilesWithExtensions(\Barberry\PostedFile\Collection $files, $skipName) {
        $filenames = array();

        foreach ($files as $name => $file) {
            if ($skipName !== $name) {
                $filenames[$name] = $this->toTempFileWithExt($file);
            }
        }

        return $filenames;
    }

    private function toTempFileWithExt(\Barberry\PostedFile $file) {
        $filename = tempnam($this->tempPath, 'ooparser_') . '.' . $file->getStandardExtension();
        file_put_contents($filename, $file->bin);
        return $filename;
    }

    private function isContentTypeSupported($bin) {
        return in_array(ContentType::byString($bin)->standartExtention(), array('ott', 'odt', 'ots'));
    }

    private function getVars(\Barberry\PostedFile\Collection $allFiles, array $vars) {
        $varsJsonFile = $allFiles['templateVars'];
        if (!is_null($varsJsonFile)) {
            unset($allFiles['templateVars']);

            $varsFromFile = json_decode($varsJsonFile->bin, true);
            if (is_array($varsFromFile)) {
                return array_merge($vars, $varsFromFile);
            }
        }

        return $vars;
    }

    private function noOtherFiles(\Barberry\PostedFile\Collection $files, $skipName) {
        foreach ($files as $name => $file) {
            if ($skipName !== $name) {
                return false;
            }
        }

        return true;
    }

}
