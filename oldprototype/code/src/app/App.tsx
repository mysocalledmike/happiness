import { useState } from 'react';
import { HomePage } from './components/HomePage';
import { SenderDashboard } from './components/SenderDashboard';
import { HappinessPage } from './components/HappinessPage';
import { AdminDashboard } from './components/AdminDashboard';

type Page = 'home' | 'dashboard' | 'happiness' | 'admin';

interface User {
  name: string;
  email: string;
  character: string;
}

interface MessageData {
  recipientEmail: string;
  recipientName: string;
  message: string;
}

export default function App() {
  const [currentPage, setCurrentPage] = useState<Page>('home');
  const [user, setUser] = useState<User | null>(null);
  const [isFirstVisit, setIsFirstVisit] = useState(false);
  const [selectedMessage, setSelectedMessage] = useState<MessageData | null>(null);

  const handleGetStarted = (userData: User) => {
    setUser(userData);
    setCurrentPage('dashboard');
    setIsFirstVisit(true);
  };

  const handleViewMessage = (messageData: MessageData) => {
    setSelectedMessage(messageData);
    setCurrentPage('happiness');
  };

  const handleCreateOwn = () => {
    setCurrentPage('home');
  };

  const handleNavigateHome = () => {
    setCurrentPage('home');
  };

  const handleAdminAccess = () => {
    setCurrentPage('admin');
  };

  return (
    <div className="min-h-screen">
      {/* Pages */}
      {currentPage === 'home' && <HomePage onGetStarted={handleGetStarted} />}

      {currentPage === 'dashboard' && user && (
        <SenderDashboard
          user={user}
          onViewMessage={handleViewMessage}
          onNavigateHome={handleNavigateHome}
          isFirstVisit={isFirstVisit}
          onDismissFirstVisit={() => setIsFirstVisit(false)}
        />
      )}

      {currentPage === 'happiness' && user && selectedMessage && (
        <HappinessPage
          sender={{
            name: user.name,
            character: user.character,
            totalSmiles: 3,
          }}
          receiverEmail={selectedMessage.recipientEmail}
          receiverName={selectedMessage.recipientName}
          message={selectedMessage.message}
          onCreateOwn={handleCreateOwn}
        />
      )}

      {currentPage === 'admin' && <AdminDashboard onNavigateHome={handleNavigateHome} />}
    </div>
  );
}