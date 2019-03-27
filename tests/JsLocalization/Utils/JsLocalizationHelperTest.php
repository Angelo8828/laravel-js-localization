<?php

use JsLocalization\Facades\JsLocalizationHelper;

class JsLocalizationHelperTest extends TestCase
{

    private $tmpFilePath;

    protected $additionalMessageKeys = [
            'additional' => [
                'message1',
                'message2'
            ]
        ];

    protected $additionalMessageKeysFlat = [
            'additional.message1', 'additional.message2'
        ];


    protected $testMessagesFlat = [
            'en' => [
                'test1' => "Text for test1",
                'prefix1' => [
                    'prefix2' => [
                        'test2' => "Text for test2",
                        'test3' => "Text for test3"
                    ],
                    'test4' => "Text for test4"
                ]
            ]
        ];

    protected $testKeys = [
            'test1',
            'prefix1' => [
                'prefix2' => [
                    'test2', 'test3'
                ],
                'test4'
            ]
        ];

    protected $testKeysFlat = [
            'test1',
            'prefix1.prefix2.test2',
            'prefix1.prefix2.test3',
            'prefix1.test4'
        ];

    protected function setUpTestMessagesFile($filePath)
    {
        $fileContents = '<?php return ' . var_export($this->testMessagesFlat['en'], true) . ';';
        file_put_contents($filePath, $fileContents);

        $prefix = preg_replace('/\.php$/i', '', basename($filePath));

        return $prefix;
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->tmpFilePath = tempnam('/tmp', '');
        unlink($this->tmpFilePath);

        $this->tmpFilePath .= '.php';
        touch($this->tmpFilePath);
    }

    public function tearDown(): void
    {
        unlink($this->tmpFilePath);

        parent::tearDown();
    }

    public function testResolveMessageKeyArray()
    {
        $this->assertEquals($this->testKeysFlat, JsLocalizationHelper::resolveMessageKeyArray($this->testKeys));
    }

    public function testResolveMessageArrayToMessageKeys()
    {
        $this->assertEquals($this->testKeysFlat, JsLocalizationHelper::resolveMessageArrayToMessageKeys($this->testMessagesFlat['en']));
    }

    public function testAddingRetrieving()
    {
        JsLocalizationHelper::addMessagesToExport($this->additionalMessageKeys);

        $this->assertEquals(
            $this->additionalMessageKeysFlat,
            JsLocalizationHelper::getAdditionalMessages()
        );


        $this->addTestMessage('en', 'another', 'Another test text.');

        JsLocalizationHelper::addMessagesToExport(['another']);

        $this->assertEquals(
            array_merge($this->additionalMessageKeysFlat, ['another']),
            JsLocalizationHelper::getAdditionalMessages()
        );
    }

    public function testEventBasedAdding()
    {
        $additionalMessageKeys = $this->additionalMessageKeys;


        Event::listen('JsLocalization.registerMessages', function()
        use($additionalMessageKeys)
        {
            JsLocalizationHelper::addMessagesToExport($additionalMessageKeys);
        });

        $this->assertEquals([], JsLocalizationHelper::getAdditionalMessages());

        Event::fire('JsLocalization.registerMessages');

        $this->assertEquals(
            $this->additionalMessageKeysFlat,
            JsLocalizationHelper::getAdditionalMessages()
        );


        $this->addTestMessage('en', 'another', 'Another test text.');

        Event::listen('JsLocalization.registerMessages', function()
        {
            JsLocalizationHelper::addMessagesToExport(['another']);
        });

        Event::fire('JsLocalization.registerMessages');

        $this->assertEquals(
            array_merge($this->additionalMessageKeysFlat, ['another']),
            JsLocalizationHelper::getAdditionalMessages()
        );
    }

    public function testAddMessageFileToExport()
    {
        $prefix = 'xyz::' . $this->setUpTestMessagesFile($this->tmpFilePath);
        JsLocalizationHelper::addMessageFileToExport($this->tmpFilePath, 'xyz::');

        // since we just tested the method using a prefix without the trailing '.'
        $prefix .= '.';

        $testKeysFlat = $this->testKeysFlat;
        array_walk($testKeysFlat, function(&$key) use($prefix)
            {
                $key = $prefix . $key;
            });

        $this->assertEquals($testKeysFlat, JsLocalizationHelper::getAdditionalMessages());
    }

    public function testAddMessageFileToExportExceptionHandling()
    {
        $filePath = "/tmp/x/y/z/does-not-exist";

        $this->setExpectedException(
            'JsLocalization\Exceptions\FileNotFoundException',
            "File not found: $filePath"
        );

        JsLocalizationHelper::addMessageFileToExport($filePath, 'xyz::');
    }

}
