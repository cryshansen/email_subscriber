theres some mismapping in this module because of cache issues preventing wrecking the preduction environment
Cleanup required in this area == duplication is confusing

in production EmailSubscribeForm //email-subscribe-form on front page page block should remove the other file for purity of folder
modalform is same in both environments and is rendered using a page block to place within a modal twig file

in development EmailSubscribeForm --  //email-subscribe-form

I believe the routing is not correct and needs to be cleaned up however a block placed form technically doesnt require its own route. 

Made notes in two files that are the active files used in this modal. 

Many experimentation forms that perform different example tasks/
Ajax to improve the speed of the response as there are many processes behind that manage the submission process from db to api services.