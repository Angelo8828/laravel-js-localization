<?php

class LocalizationScriptTest extends TestCase
{
    public function testScriptRetrieval()
    {
        $response = $this->call('GET', '/js-localization/localization.js');

        $this->assertTrue($response->isOk());
        $content = $response->getContent();
        
        // Test for JS content
        
        $this->assertMatchesRegularExpression('/^!?\(?function\(.*\);/', $content);
    }
    
    public function testScriptAndTranslationCombinedRetrieval()
    {
        $response = $this->call('GET', '/js-localization/all.js');

        $this->assertTrue($response->isOk());
        $content = $response->getContent();

        // Test for JS content

        $this->assertMatchesRegularExpression('/^!?\(?function\(.*\);/', $content);

        // Test for Lang.addMessages()

        $addMessagesRegex = '/Lang\.addMessages\( (\{.*?\}) \);/x';
        $this->assertMatchesRegularExpression($addMessagesRegex, $content);
        
        // Test for Config.addConfig()

        $addConfigRegex = '/Config\.addConfig\( (\{.*?\}) \);/x';
        $this->assertMatchesRegularExpression($addConfigRegex, $content);
    }
}