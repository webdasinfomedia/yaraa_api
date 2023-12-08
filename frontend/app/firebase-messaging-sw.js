importScripts("https://www.gstatic.com/firebasejs/8.6.1/firebase-app.js");
importScripts("https://www.gstatic.com/firebasejs/8.6.1/firebase-messaging.js");

self.addEventListener('notificationclick', function (event) {
  console.debug('SW notification click event', event)

  var baseUrl = 'https://yaraamanager.com/';
  var url = baseUrl;
  var notificationType = event.notification.data.type;
  var moduleId = event.notification.data.module_id;
  var module = event.notification.data.module;
  var notifyId = event.notification.data.id;

  if (notificationType == "task_created" ||
        notificationType == "task_completed" ||
        notificationType == "task_new_message" ||
        notificationType == "task_reopened" ||
        notificationType == "task_restore" ||
        notificationType == "task" ||
        (notificationType == "meet_meeting_created" && module == "task")) {
	  url = url+'#/tasks/detail?taskId='+moduleId+'&notify='+notifyId;
  }else if (notificationType == "milestone_reopened" ||
              notificationType == "milestone_reopened" ||
              notificationType == "milestone_completed" ||
              notificationType == "added_in_project" ||
              notificationType == "project_completed" ||
              notificationType == "project_reopen") {
	  url = url+'#/projects/detail?projectId='+moduleId+'&notify='+notifyId;
  }else if (notificationType == "todo") {
	  url = url+'#/tasks/detail?todoId='+moduleId+'&notify='+notifyId;
  }else if (notificationType == "project_deleted") {
	  url = url+'#/projects';
  }else if (notificationType == "task_deleted" || notificationType== "added_in_task") {
	  url = url+'#/tasks';
  }else if (notificationType == "message_received" ||  (notificationType == "meet_meeting_created" && module == "message")) {
	  url = url+'#/messages?messageId='+moduleId+'&notify='+notifyId;
  }

	event.waitUntil(
    clients.matchAll({type: 'window', includeUncontrolled: true,}).then( windowClients => {
        // Check if there is already a window/tab open with the target URL
				console.log("windowClients.length : ");
				console.log(windowClients.length);
               for (var i = 0; i < windowClients.length; i++) {
                   var client = windowClients[i];
                   // If so, just focus it.
				   console.log(client.url);
                   var clientUrl = client.url;
                   if (clientUrl.includes(baseUrl) && 'focus' in client) {
						console.log("focus find");
						console.log('Navigate : ');
						console.log(url);
						return client.focus();
						//return client.navigate(url);
                   }
               }
               // If not, then open the target URL in a new window/tab.
               if (clients.openWindow) {
			   console.log("open in new tab");
                   return clients.openWindow(url);
               }
    })
);
})
var firebaseApp;
var messaging;
if (!firebase.apps.length) {
   firebaseApp = firebase.initializeApp({
       apiKey: "AIzaSyDpdlQkhHzPr8eEqS1q-igaFqu-t3Nt5SM",
            authDomain: "yaraa-ai.firebaseapp.com",
            projectId: "yaraa-ai",
            storageBucket: "yaraa-ai.appspot.com",
            messagingSenderId: "855681743529",
            appId: "1:855681743529:web:7a1e37514cba49a082c7b8",
            measurementId: "G-CR5FD87X17"
        }, {name: '[DEFAULT]'});
}else{
    firebaseApp = firebase.app('[DEFAULT]');
}
messaging = firebaseApp.messaging();

messaging.setBackgroundMessageHandler(function (payload) {

    console.log("Version : 4");

    const title = payload.data.title;
    const type = payload.data.type;

    const promiseChain = clients
       .matchAll({
           type: "window",
           includeUncontrolled: true
       })
       .then(windowClients => {
           for (let i = 0; i < windowClients.length; i++) {
               const windowClient = windowClients[i];
               windowClient.postMessage(payload);
           }
       })
       .then(() => {
                console.log(payload);
                console.log("stringify background: ");
                console.log(JSON.stringify(payload));

                const options = {
                     body: payload.data.description,
                     icon: '/favicon.png',
                     data: {
                        module_id : payload.data.module_id,
                        type : payload.data.type,
                        id : payload.data.id
                     }
                   };
                 return registration.showNotification(title, options);
       });
   return promiseChain;
});