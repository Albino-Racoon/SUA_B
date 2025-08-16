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
    case 'POST': // CREATE - dodaj log
      const { action, user_id, details } = req.body;
      return res.status(201).json({ 
        message: 'Log dodan', 
        data: { 
          id: Date.now(), 
          action, 
          user_id, 
          details, 
          timestamp: new Date().toISOString() 
        } 
      });
      
    case 'GET': // READ - pridobi statistike
      return res.status(200).json({ 
        message: 'Statistike pridobljene', 
        data: { 
          total_logs: 150, 
          today_logs: 25, 
          popular_actions: ['quiz', 'search', 'professor_view'],
          last_updated: new Date().toISOString()
        } 
      });
      
    case 'PUT': // UPDATE - posodobi log
      const { id, ...updateData } = req.body;
      return res.status(200).json({ 
        message: 'Log posodobljen', 
        data: { 
          id, 
          ...updateData, 
          updated_at: new Date().toISOString() 
        } 
      });
      
    case 'DELETE': // DELETE - izbriši log
      const { id: deleteId } = req.query;
      return res.status(200).json({ 
        message: 'Log izbrisan', 
        data: { 
          id: deleteId, 
          deleted_at: new Date().toISOString() 
        } 
      });
      
    default:
      return res.status(405).json({ error: 'Metoda ni podprta' });
  }
}
