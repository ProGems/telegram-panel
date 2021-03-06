<?php

namespace Telegramapp\Telegram\Commands;

use Telegramapp\Telegram\Answers\Answerable;
use Telegramapp\Telegram\Api;
use Telegramapp\Telegram\Objects\Update;
use Nordal\Data\Models\User;
use DB;

/**
 * Class Command.
 *
 *
 * @method mixed replyWithMessage($use_sendMessage_parameters)       Reply Chat with a message. You can use all the sendMessage() parameters except chat_id.
 * @method mixed replyWithPhoto($use_sendPhoto_parameters)           Reply Chat with a Photo. You can use all the sendPhoto() parameters except chat_id.
 * @method mixed replyWithAudio($use_sendAudio_parameters)           Reply Chat with an Audio message. You can use all the sendAudio() parameters except chat_id.
 * @method mixed replyWithVideo($use_sendVideo_parameters)           Reply Chat with a Video. You can use all the sendVideo() parameters except chat_id.
 * @method mixed replyWithVoice($use_sendVoice_parameters)           Reply Chat with a Voice message. You can use all the sendVoice() parameters except chat_id.
 * @method mixed replyWithDocument($use_sendDocument_parameters)     Reply Chat with a Document. You can use all the sendDocument() parameters except chat_id.
 * @method mixed replyWithSticker($use_sendSticker_parameters)       Reply Chat with a Sticker. You can use all the sendSticker() parameters except chat_id.
 * @method mixed replyWithLocation($use_sendLocation_parameters)     Reply Chat with a Location. You can use all the sendLocation() parameters except chat_id.
 * @method mixed replyWithChatAction($use_sendChatAction_parameters) Reply Chat with a Chat Action. You can use all the sendChatAction() parameters except chat_id.
 */
abstract class Command implements CommandInterface
{
    use Answerable;

    /**
     * The name of the Telegram command.
     * Ex: help - Whenever the user sends /help, this would be resolved.
     *
     * @var string
     */
    protected $name;

    /**
     * Command Aliases
     * Helpful when you want to trigger command with more than one name.
     *
     * @var array
     */
    protected $aliases = [];

    /**
     * @var string The Telegram command description.
     */
    protected $description;

    /**
     * @var string Arguments passed to the command.
     */
    protected $arguments;

    /**
     * Get Command Name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get Command Aliases
     *
     * @return array
     */
    public function getAliases()
    {
        return $this->aliases;
    }

    /**
     * Set Command Name.
     *
     * @param $name
     *
     * @return Command
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get Command Description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set Command Description.
     *
     * @param $description
     *
     * @return Command
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get Arguments passed to the command.
     *
     * @return string
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Returns an instance of Command Bus.
     *
     * @return CommandBus
     */
    public function getCommandBus()
    {
        return $this->telegram->getCommandBus();
    }

    /**
     * @inheritDoc
     */
    public function make(Api $telegram, $arguments, Update $update)
    {
        $this->telegram = $telegram;
        $this->arguments = $arguments;
        $this->update = $update;

        return $this->handle($arguments);
    }

    /**
     * Helper to Trigger other Commands.
     *
     * @param      $command
     * @param null $arguments
     *
     * @return mixed
     */
    protected function triggerCommand($command, $arguments = null)
    {
        return $this->getCommandBus()->execute($command, $arguments ?: $this->arguments, $this->update);
    }

    public function updateChatId (){

    
        $message = trim($this->update->getMessage()->getText());
        $key = '';
        if (($arr = preg_split('/ /', $message)) && count($arr) > 0){
            $key = $arr[1];
        }
        //$key = split($message, ' ')[1];
        $chat_id = $this->update->getMessage()->getChat()->getId();

        $user_id = $this->update->getMessage()->getChat();

        $decode = json_decode($user_id); 

        $userId = $decode->id;
            
        // $this->replyWithMessage(['text' => "$key, $chat_id, $message"]);
        DB::table('users')->where('token_key', '=', $key)->update(['chat_id' => $chat_id, 'user_id' => $userId]);
    }
    public function updateGroupChatId (){

    
        $message = trim($this->update->getMessage()->getText());
        $key = '';
        if (($arr = preg_split('/ /', $message)) && count($arr) > 0){
            $key = $arr[1];
        }
        //$key = split($message, ' ')[1];
        $chat_id = $this->update->getMessage()->getChat()->getId();


        $group_slug = $this->update->getMessage()->getChat();

        $decode = json_decode($group_slug); 

        $slug = $decode->title;

            
        // $this->replyWithMessage(['text' => "$key, $chat_id, $message"]);
        DB::table('groups')->where('group_nr', '=', $key)->update(['chat_id' => $chat_id, 'group_slug' => $slug ]);
    }
    public function getChatMembers()
    {
        $members = $this->update->getMessage()->getChat()->getId();

        $user_id = $this->update->getMessage()->getChat();

        $decode = json_decode($user_id); 

        $userId = $decode->id;

        $count = $this->telegram->getChatMember(['chat_id' => $members, 'user_id' => $userId]);

        $user = json_decode($count, true);

        $first = $user['user']["first_name"];
        $last = $user['user']["last_name"];
            
        $this->replyWithChatAction(['action' => Actions::TYPING]);
        
        $this->replyWithMessage(['text' => "$first $last"]);
    }

    public function inviteLink()
    {
        $message = trim($this->update->getMessage()->getText());
        $key = '';
        if (($arr = preg_split('/[\s,]+/', $message)) && count($arr) > 0){
            $key = $arr[1]; $link = $arr[2];
        };
        
        

        DB::table('groups')->where('group_nr', '=', $key)->update(['invite_link' => $link]);

    }

    /**
     * {@inheritdoc}
     */
    abstract public function handle($arguments);
}
