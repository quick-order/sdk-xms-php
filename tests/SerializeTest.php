<?php

/**
 * Contains tests of the Serialize class.
 *
 * PHP versions 5 and 7
 *
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 */

use Clx\Xms as X;
use Clx\Xms\Api as XA;

class SerializeTest extends PHPUnit\Framework\TestCase
{

    public function testBatchCreateText()
    {
        $batch = new XA\MtTextSmsBatchCreate();
        $batch->sender = '12345';
        $batch->recipients = ['987654321', '123456789'];
        $batch->body = 'Hello, ${name}!';
        $batch->parameters['name'] = [
            '987654321' => 'Mary',
            '123456789' => 'Joe',
            'default' => 'you'
        ];
        $batch->deliveryReport = XA\ReportType::NONE;
        $batch->sendAt = new \DateTime('2016-12-01T11:03:13.192Z');
        $batch->expireAt = new \DateTime('2016-12-04T11:03:13.192Z');
        $batch->callbackUrl = "http://localhost/callback";

        $actual = X\Serialize::textBatch($batch);

        $expected = <<<'EOD'
{
    "body": "Hello, ${name}!",
    "delivery_report": "none",
    "send_at": "2016-12-01T11:03:13+00:00",
    "expire_at": "2016-12-04T11:03:13+00:00",
    "from": "12345",
    "to": [
        "987654321",
        "123456789"
    ],
    "parameters": {
        "name": {
            "987654321": "Mary",
            "123456789": "Joe",
            "default": "you"
        }
    },
    "callback_url": "http://localhost/callback",
    "type": "mt_text"
}
EOD;

        $this->assertJsonStringEqualsJsonString($expected, $actual);
    }

    public function testBatchCreateBinary()
    {
        $batch = new XA\MtBinarySmsBatchCreate();
        $batch->sender = '12345';
        $batch->recipients = ['987654321', '123456789'];
        $batch->body = "\x00\x01\x02\x03";
        $batch->udh = "\xff\xfe\xfd";
        $batch->deliveryReport = XA\ReportType::SUMMARY;
        $batch->expireAt = new \DateTime('2016-12-17T08:15:29.969Z');
        $batch->tags = [ "tag1", "таг2" ];

        $actual = X\Serialize::binaryBatch($batch);

        $expected = <<<'EOD'
{
    "body": "AAECAw==",
    "delivery_report": "summary",
    "expire_at": "2016-12-17T08:15:29+00:00",
    "from": "12345",
    "tags": [ "tag1", "таг2" ],
    "to": [
        "987654321",
        "123456789"
    ],
    "type": "mt_binary",
    "udh": "fffefd"
}
EOD;

        $this->assertJsonStringEqualsJsonString($expected, $actual);
    }

    public function testGroupCreate()
    {
        $group = new XA\GroupCreate();
        $group->name = 'test name';
        $group->members = ['123456789', '987654321'];
        $group->childGroups = ['group1', 'group2'];
        $group->autoUpdate = new XA\GroupAutoUpdate(
            '12345', ['ADD', 'plz'], ['REMOVE', 'ME']
        );

        $actual = X\Serialize::group($group);

        $expected = <<<'EOD'
{
    "auto_update": {
        "to": "12345",
        "add": {
            "first_word": "ADD",
            "second_word": "plz"
        },
        "remove": {
            "first_word": "REMOVE",
            "second_word": "ME"
        }
    },
    "members": ["123456789", "987654321"],
    "child_groups": ["group1", "group2"],
    "name": "test name"
}
EOD;

        $this->assertJsonStringEqualsJsonString($expected, $actual);
    }

    public function testGroupUpdateEverything()
    {
        $groupUpdate = new XA\GroupUpdate();
        $groupUpdate->name = 'new name';
        $groupUpdate->memberInsertions = ['123456789'];
        $groupUpdate->memberRemovals = ['987654321', '4242424242'];
        $groupUpdate->childGroupInsertions = ['groupId1', 'groupId2'];
        $groupUpdate->childGroupRemovals = ['groupId3'];
        $groupUpdate->addFromGroup = "group1";
        $groupUpdate->removeFromGroup = "group2";
        $groupUpdate->autoUpdate = new XA\GroupAutoUpdate(
            '1111', ['kw0', 'kw1'], ['kw2', 'kw3']
        );

        $actual = X\Serialize::groupUpdate($groupUpdate);

        $expected = <<<'EOD'
{
  "name": "new name",
  "add": [ "123456789" ],
  "remove": [ "987654321", "4242424242" ],
  "child_groups_add": [ "groupId1", "groupId2" ],
  "child_groups_remove": [ "groupId3" ],
  "add_from_group": "group1",
  "remove_from_group": "group2",
  "auto_update": {
    "to": "1111",
    "add": { "first_word": "kw0", "second_word": "kw1" },
    "remove": { "first_word": "kw2", "second_word": "kw3" }
  }
}
EOD;

        $this->assertJsonStringEqualsJsonString($expected, $actual);
    }

    public function testGroupUpdateMinimal()
    {
        $groupUpdate = new XA\GroupUpdate();

        $actual = X\Serialize::groupUpdate($groupUpdate);
        $expected = '{}';

        $this->assertJsonStringEqualsJsonString($expected, $actual);
    }

    public function testGroupUpdateResets()
    {
        $groupUpdate = new XA\GroupUpdate();
        $groupUpdate->name = XA\Reset::reset();
        $groupUpdate->autoUpdate = XA\Reset::reset();

        $actual = X\Serialize::groupUpdate($groupUpdate);

        $expected = <<<'EOD'
{
  "name": null,
  "auto_update": null
}
EOD;

        $this->assertJsonStringEqualsJsonString($expected, $actual);
    }

    public function testTags()
    {
        $actual = X\Serialize::tags(["tag1", "tag2"]);
        $expected = '{ "tags": ["tag1", "tag2"] }';

        $this->assertJsonStringEqualsJsonString($expected, $actual);
    }

    public function testTagsUpdate()
    {
        $actual = X\Serialize::tagsUpdate(['tag_1', 'tag_2'], ['tag']);
        $expected = '{ "add": ["tag_1", "tag_2"], "remove": ["tag"] }';

        $this->assertJsonStringEqualsJsonString($expected, $actual);
    }

}

?>