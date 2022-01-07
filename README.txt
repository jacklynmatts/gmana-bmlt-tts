DEPRECATED: If you're looking for a solution to read BMLT meeting information into a phone system, you should use Yap (https://bmlt.app/yap/). This code was written and deployed before Yap was released.


Small Cron script to transform structured data from BMLT server into spoken word audio.

1) Retrieve structured data from BMLT
2) Parse structured data into natural dialogue
3) Use AWS Polly TTS
4) Format conversion through CloudConvert
5) Move files and restart Asterisk to apply changes
