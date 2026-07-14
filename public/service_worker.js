self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    event.waitUntil(clients.matchAll({
        type: "window"
    }).then((clientList) => {
        if (clients.openWindow) {
            return clients.openWindow(event.notification.tag);
        }
    }));
});
self.addEventListener("push", (event) => {
    const data = event.data.json();
    self.registration.showNotification(data.title, {
        body: data.body,
        icon: data.icon,
        image: data.image,
        tag: data.tag
    });
});