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
    }

    /**
     * @param array $vars
     * @param \Barberry\PostedFile[] $allFiles
     * @return \Barberry\PostedFile
     */
    public function filter(array $vars, array $allFiles = array()) {
        if (empty($allFiles)) {
            return null;
        }

        $file = array_shift($allFiles);

        if (!strlen($file->bin) || !$this->isContentTypeSupported($file->bin)) {
            return null;
        }

        $this->tbs->PlugIn(TBS_INSTALL, OPENTBS_PLUGIN);
        $tempFileName = $this->toTempFileWithExt($file->bin);
        $this->tbs->LoadTemplate($tempFileName, OPENTBS_ALREADY_UTF8);

        $tempFileNames = $this->savePostedFilesWithExtensions($allFiles);
        $vars += $tempFileNames;

        $this->tbs->VarRef = $vars;

        foreach ($vars as $key => $val) {
            if (is_array($val)) {
                $this->tbs->MergeBlock($key, !empty($val[0]) ? $val : array());
            } else {
                $this->tbs->MergeField($key, $val);
            }
        }

        $this->tbs->Show(OPENTBS_STRING);

        foreach ($tempFileNames as $filename) {
            unlink($filename);
        }
        unlink($tempFileName);

        return new \Barberry\PostedFile($this->tbs->Source, $file->filename);
    }

//--------------------------------------------------------------------------------------------------

    /**
     * @param \Barberry\PostedFile[] $files
     * @return array
     */
    private function savePostedFilesWithExtensions($files) {
        $filenames = array();

        foreach ($files as $name => $file) {
            $filenames[$name] = $this->toTempFileWithExt($file->bin);
        }

        return $filenames;
    }

    private function toTempFileWithExt($template) {
        $filename = tempnam($this->tempPath, 'ooparser_') . '.' . ContentType::byString($template)->standartExtention();
        file_put_contents($filename, $template);
        return $filename;
    }

    private function isContentTypeSupported($bin) {
        return in_array(ContentType::byString($bin)->standartExtention(), array('ott', 'odt', 'ots'));
    }

}
