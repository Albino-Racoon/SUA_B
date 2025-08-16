export default async function handler(req, res) {
  const { method } = req;
  
  // CORS headers
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');
  
  if (method === 'OPTIONS') {
    return res.status(200).end();
  }
  
  switch (method) {
    case 'POST': // CREATE - dodaj notifikacijo
      const { user_id, message, type, priority } = req.body;
      return res.status(201).json({ 
        message: 'Notifikacija dodana', 
        data: { 
          id: Date.now(), 
          user_id, 
          message, 
          type, 
          priority: priority || 'normal',
          read: false, 
          timestamp: new Date().toISOString() 
        } 
      });
      
    case 'GET': // READ - pridobi notifikacije
      const { user_id: queryUserId, unread_only } = req.query;
      let notifications = [
        {
          id: 1,
          user_id: 1,
          message: 'Dobrodošli v aplikacijo!',
          type: 'welcome',
          priority: 'normal',
          read: false,
          timestamp: new Date().toISOString()
        },
        {
          id: 2,
          user_id: 1,
          message: 'Novi komentarji so na voljo',
          type: 'update',
          priority: 'high',
          read: true,
          timestamp: new Date().toISOString()
        }
      ];
      
      if (queryUserId) {
        notifications = notifications.filter(n => n.user_id == queryUserId);
      }
      
      if (unread_only === 'true') {
        notifications = notifications.filter(n => !n.read);
      }
      
      return res.status(200).json({ 
        message: 'Notifikacije pridobljene', 
        data: notifications,
        count: notifications.length
      });
      
    case 'PUT': // UPDATE - označi kot prebrano
      const { id, read, ...updateData } = req.body;
      return res.status(200).json({ 
        message: 'Notifikacija posodobljena', 
        data: { 
          id, 
          read, 
          ...updateData,
          updated_at: new Date().toISOString() 
        } 
      });
      
    case 'DELETE': // DELETE - izbriši notifikacijo
      const { id: deleteId } = req.query;
      return res.status(200).json({ 
        message: 'Notifikacija izbrisana', 
        data: { 
          id: deleteId, 
          deleted_at: new Date().toISOString() 
        } 
      });
      
    default:
      return res.status(405).json({ error: 'Metoda ni podprta' });
  }
}
