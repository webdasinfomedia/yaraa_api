db.createUser(
    {
        user: "root",
        pwd: "example",
        roles: [
            {
                role: "readWrite",
                db: "test"
            }
        ]
    }
)

db.createCollection('test_delete-me');
