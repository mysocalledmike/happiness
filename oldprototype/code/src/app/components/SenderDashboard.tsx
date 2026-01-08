import { useState } from 'react';
import { Button } from './ui/button';
import { Input } from './ui/input';
import { Textarea } from './ui/textarea';
import { Send, X, Globe, TrendingUp, Mail, Copy, Check, Trash2, Smile, Pencil } from 'lucide-react';
import { Logo } from './Logo';
import { GlobalProgressModal } from './GlobalProgressModal';
import { CompanyLeaderboardModal } from './CompanyLeaderboardModal';
import { MessageCard } from './MessageCard';
import { GLOBAL_SMILES, COMPANY_NAME, COMPANY_SMILES } from '../constants';

interface Message {
  id: string;
  recipientName: string;
  recipientEmail: string;
  message: string;
  sentDate: string;
  read: boolean;
  readDate?: string;
  createdAt: string;
}

interface SenderDashboardProps {
  user: {
    name: string;
    character: string;
    email?: string;
  };
  onViewMessage: (messageData: { recipientEmail: string; recipientName: string; message: string }) => void;
  onNavigateHome: () => void;
  isFirstVisit?: boolean;
  onDismissFirstVisit?: () => void;
}

export function SenderDashboard({ user, onViewMessage, onNavigateHome, isFirstVisit, onDismissFirstVisit }: SenderDashboardProps) {
  const [messages, setMessages] = useState<Message[]>([
    {
      id: '1',
      recipientName: 'Sarah',
      recipientEmail: 'sarah@example.com',
      message: 'Thank you for always being there when I needed help on the project! Your positive energy made such a difference.',
      sentDate: 'Mar 11',
      read: false,
      createdAt: new Date('2024-03-10').toISOString(),
    },
    {
      id: '2',
      recipientName: 'Mike',
      recipientEmail: 'mike@example.com',
      message: 'I really appreciated your feedback during the presentation. You helped me see things from a new perspective!',
      sentDate: 'Mar 12',
      read: true,
      readDate: 'Mar 13',
      createdAt: new Date('2024-03-11').toISOString(),
    },
  ]);
  const [newMessage, setNewMessage] = useState({ recipientName: '', recipientEmail: '', message: '' });
  const [showProgress, setShowProgress] = useState(false);
  const [showLeaderboard, setShowLeaderboard] = useState(false);
  const [showCopied, setShowCopied] = useState(false);
  const [confirmDeleteId, setConfirmDeleteId] = useState<string | null>(null);
  const [showSendConfirm, setShowSendConfirm] = useState(false);
  const [viewMode, setViewMode] = useState<'list' | 'grid'>('list');
  
  // Inline editing state
  const [isEditingName, setIsEditingName] = useState(false);
  const [editedName, setEditedName] = useState(user.name);
  const [isEditingEmail, setIsEditingEmail] = useState(false);
  const [editedEmail, setEditedEmail] = useState(user.email || '');

  const totalSmiles = messages.length;

  // Generate obfuscated URL for this user
  const dashboardUrl = `https://onetrillionsmiles.com/send/${btoa(user.name).substring(0, 8).toLowerCase().replace(/[^a-z0-9]/g, '')}`;

  // Sort messages in reverse chronological order (newest first)
  const sortedMessages = [...messages].sort((a, b) => 
    new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime()
  );

  const handleSendMessage = () => {
    const today = new Date();
    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const sentDate = `${monthNames[today.getMonth()]} ${today.getDate()}`;
    
    setMessages([
      {
        id: Date.now().toString(),
        recipientName: newMessage.recipientName,
        recipientEmail: newMessage.recipientEmail,
        message: newMessage.message,
        sentDate,
        read: false,
        createdAt: new Date().toISOString(),
      },
      ...messages,
    ]);
    setNewMessage({ recipientName: '', recipientEmail: '', message: '' });
    setShowSendConfirm(false);
  };

  const handleDelete = (id: string) => {
    setMessages(messages.filter((m) => m.id !== id));
    setConfirmDeleteId(null);
  };

  const handleCopyLink = () => {
    navigator.clipboard.writeText(dashboardUrl);
    setShowCopied(true);
    setTimeout(() => setShowCopied(false), 2000);
  };

  const handleSaveName = () => {
    if (editedName.trim()) {
      user.name = editedName.trim();
      setIsEditingName(false);
    }
  };

  const handleCancelName = () => {
    setEditedName(user.name);
    setIsEditingName(false);
  };

  const handleSaveEmail = () => {
    if (editedEmail.trim()) {
      user.email = editedEmail.trim();
      setIsEditingEmail(false);
    }
  };

  const handleCancelEmail = () => {
    setEditedEmail(user.email || '');
    setIsEditingEmail(false);
  };

  const handleNameKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      handleSaveName();
    } else if (e.key === 'Escape') {
      handleCancelName();
    }
  };

  const handleEmailKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      handleSaveEmail();
    } else if (e.key === 'Escape') {
      handleCancelEmail();
    }
  };

  const canSend = newMessage.recipientName && newMessage.recipientEmail && newMessage.message;

  return (
    <div className="min-h-screen bg-gradient-to-br from-orange-50 via-pink-50 to-yellow-50">
      {/* Sticky Header */}
      <div className="sticky top-0 z-10 bg-gradient-to-r from-orange-500 via-pink-500 to-orange-500 shadow-lg">
        <div className="max-w-6xl mx-auto px-6 py-4">
          <div className="flex items-center justify-between">
            <button 
              onClick={onNavigateHome}
              className="hover:opacity-80 transition-opacity"
            >
              <Logo onBanner={true} />
            </button>
            <div className="flex items-center gap-4">
              <button
                onClick={() => setShowProgress(true)}
                className="flex items-center gap-2 text-white/90 hover:text-white transition-colors font-lora"
              >
                <Globe className="w-4 h-4" />
                <span className="text-sm">
                  <span className="font-bold">{GLOBAL_SMILES.toLocaleString()}</span> of 1T smiles
                </span>
              </button>
              <button
                onClick={() => setShowLeaderboard(true)}
                className="flex items-center gap-2 text-white/90 hover:text-white transition-colors font-lora"
              >
                <TrendingUp className="w-4 h-4" />
                <span className="text-sm">
                  <span className="font-bold">{COMPANY_SMILES}</span> {COMPANY_NAME} smiles
                </span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <div className="max-w-5xl mx-auto px-6 py-12">
        {/* First Visit Notification */}
        {isFirstVisit && (
          <div className="bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-300 rounded-3xl p-6 mb-8 relative">
            <button
              onClick={onDismissFirstVisit}
              className="absolute top-4 right-4 text-gray-400 hover:text-gray-600"
            >
              <X className="w-5 h-5" />
            </button>
            <div className="flex items-start gap-4 pr-8">
              <div className="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0">
                <Mail className="w-6 h-6 text-white" />
              </div>
              <div>
                <h3 className="text-xl font-bold text-gray-900 mb-2 font-poppins">
                  Welcome to your Smile page!
                </h3>
                <p className="text-gray-700 font-lora">
                  This pages keeps track of Smiles you've sent and how many Smiles you've created on others. Check your email to confirm it's really you and to easily get back to this page. 
                </p>
              </div>
            </div>
          </div>
        )}

        {/* Masthead */}
        <div className="bg-white rounded-3xl p-10 shadow-lg mb-8">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-6">
              <div className="w-24 h-24 bg-gradient-to-br from-orange-400 to-pink-500 rounded-full flex items-center justify-center text-5xl">
                {user.character}
              </div>
              <div className="space-y-1">
                {/* Name - Inline Editable */}
                {isEditingName ? (
                  <div className="flex items-center gap-2">
                    <input
                      type="text"
                      value={editedName}
                      onChange={(e) => setEditedName(e.target.value)}
                      onKeyDown={handleNameKeyDown}
                      onBlur={handleSaveName}
                      autoFocus
                      className="text-4xl font-bold text-gray-900 font-poppins border-b-2 border-orange-400 focus:outline-none bg-transparent"
                    />
                    <button
                      onClick={handleSaveName}
                      className="text-green-600 hover:text-green-700"
                      title="Save"
                    >
                      <Check className="w-5 h-5" />
                    </button>
                    <button
                      onClick={handleCancelName}
                      className="text-gray-400 hover:text-gray-600"
                      title="Cancel"
                    >
                      <X className="w-5 h-5" />
                    </button>
                  </div>
                ) : (
                  <button
                    onClick={() => setIsEditingName(true)}
                    className="group flex items-center gap-2 hover:opacity-80 transition-opacity"
                  >
                    <h1 className="text-4xl font-bold text-gray-900 font-poppins">{user.name}</h1>
                    <Pencil className="w-4 h-4 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity" />
                  </button>
                )}

                {/* Email - Inline Editable */}
                {isEditingEmail ? (
                  <div className="flex items-center gap-2 mt-1">
                    <input
                      type="email"
                      value={editedEmail}
                      onChange={(e) => setEditedEmail(e.target.value)}
                      onKeyDown={handleEmailKeyDown}
                      onBlur={handleSaveEmail}
                      autoFocus
                      className="text-sm text-gray-600 font-lora border-b border-orange-400 focus:outline-none bg-transparent"
                      placeholder="your@email.com"
                    />
                    <button
                      onClick={handleSaveEmail}
                      className="text-green-600 hover:text-green-700"
                      title="Save"
                    >
                      <Check className="w-3.5 h-3.5" />
                    </button>
                    <button
                      onClick={handleCancelEmail}
                      className="text-gray-400 hover:text-gray-600"
                      title="Cancel"
                    >
                      <X className="w-3.5 h-3.5" />
                    </button>
                  </div>
                ) : (
                  <button
                    onClick={() => setIsEditingEmail(true)}
                    className="group flex items-center gap-2 hover:opacity-80 transition-opacity"
                  >
                    <span className="text-sm text-gray-600 font-lora">
                      {user.email || 'Add your email'}
                    </span>
                    <Pencil className="w-3 h-3 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity" />
                  </button>
                )}

                {/* URL with copy */}
                <button
                  onClick={handleCopyLink}
                  className="flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 transition-colors group font-lora"
                >
                  <span className="font-mono bg-gray-100 px-3 py-1 rounded-full">{dashboardUrl}</span>
                  {showCopied ? (
                    <Check className="w-4 h-4 text-green-500" />
                  ) : (
                    <Copy className="w-4 h-4 group-hover:text-orange-500" />
                  )}
                </button>
              </div>
            </div>
            <div className="text-right">
              <div className="text-6xl font-bold bg-gradient-to-r from-orange-600 to-pink-600 bg-clip-text text-transparent font-poppins">
                {totalSmiles}
              </div>
              <p className="text-lg text-gray-600 font-lora mt-2">
                smile{totalSmiles !== 1 ? 's' : ''} created
              </p>
            </div>
          </div>
        </div>

        {/* Create Message Section */}
        <div className="mb-8">
          <h2 className="text-3xl font-bold text-gray-900 mb-6 font-poppins">Send smile</h2>
          
          <div className="bg-white rounded-3xl p-8 shadow-lg">
            <div className="space-y-4">
              <div>
                <label className="block text-sm text-gray-700 mb-2 font-lora">Name</label>
                <Input
                  type="text"
                  value={newMessage.recipientName}
                  onChange={(e) =>
                    setNewMessage({ ...newMessage, recipientName: e.target.value })
                  }
                  placeholder="Sarah"
                  className="bg-white border-gray-200 focus:border-orange-400 focus:ring-orange-400 font-lora"
                />
              </div>
              <div>
                <label className="block text-sm text-gray-700 mb-2 font-lora">Email</label>
                <Input
                  type="email"
                  value={newMessage.recipientEmail}
                  onChange={(e) =>
                    setNewMessage({ ...newMessage, recipientEmail: e.target.value })
                  }
                  placeholder="sarah@example.com"
                  className="bg-white border-gray-200 focus:border-orange-400 focus:ring-orange-400 font-lora"
                />
              </div>
              <div>
                <label className="block text-sm text-gray-700 mb-2 font-lora">Your message</label>
                <Textarea
                  value={newMessage.message}
                  onChange={(e) =>
                    setNewMessage({ ...newMessage, message: e.target.value })
                  }
                  placeholder="Write something that will make them smile..."
                  rows={6}
                  className="bg-white border-gray-200 focus:border-orange-400 focus:ring-orange-400 resize-none font-lora"
                />
              </div>
              <Button
                onClick={() => setShowSendConfirm(true)}
                disabled={!canSend}
                className="w-full bg-gradient-to-r from-orange-500 to-pink-500 hover:from-orange-600 hover:to-pink-600 text-white py-6 text-lg font-poppins"
              >
                <Send className="w-5 h-5 mr-2" />
                Send smile
              </Button>
            </div>
          </div>
        </div>

        {/* Sent Messages Section */}
        <div>
          <h2 className="text-3xl font-bold text-gray-900 mb-6 font-poppins">Smiles sent</h2>
          
          {sortedMessages.length === 0 ? (
            <div className="bg-white rounded-3xl p-12 shadow-lg text-center text-gray-500">
              <p className="font-lora">No messages sent yet. Create your first one above!</p>
            </div>
          ) : (
            <div className="space-y-6">
              {sortedMessages.map((msg) => (
                <MessageCard
                  key={msg.id}
                  senderName={user.name}
                  senderCharacter={user.character}
                  totalSmiles={totalSmiles}
                  message={msg.message}
                  recipientEmail={msg.recipientEmail}
                  recipientName={msg.recipientName}
                  sentDate={msg.sentDate}
                  read={msg.read}
                  readDate={msg.readDate}
                  onPreview={() => onViewMessage({ recipientEmail: msg.recipientEmail, recipientName: msg.recipientName, message: msg.message })}
                  onDelete={() => setConfirmDeleteId(msg.id)}
                />
              ))}
            </div>
          )}
        </div>
      </div>

      {/* Send Confirmation Dialog with Preview */}
      {showSendConfirm && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 overflow-y-auto" onClick={() => setShowSendConfirm(false)}>
          <div className="bg-gradient-to-br from-orange-50 via-pink-50 to-yellow-50 rounded-3xl p-8 max-w-3xl w-full shadow-2xl relative my-8" onClick={(e) => e.stopPropagation()}>
            <button
              onClick={() => setShowSendConfirm(false)}
              className="absolute top-4 right-4 text-gray-400 hover:text-gray-600 z-10"
            >
              <X className="w-6 h-6" />
            </button>
            
            <div className="text-center mb-6">
              <h3 className="text-2xl font-bold text-gray-900 mb-2 font-poppins">
                Send this smile?
              </h3>
              <p className="text-gray-600 font-lora">
                {newMessage.recipientEmail} will receive an email with a link to this smile.
              </p>
            </div>

            {/* Full Preview */}
            <div className="mb-6">
              <MessageCard
                senderName={user.name}
                senderCharacter={user.character}
                totalSmiles={totalSmiles}
                message={newMessage.message}
                recipientEmail={newMessage.recipientEmail}
                recipientName={newMessage.recipientName}
              />
            </div>

            <div className="flex gap-3">
              <Button
                onClick={() => setShowSendConfirm(false)}
                variant="outline"
                className="flex-1 font-poppins"
              >
                Cancel
              </Button>
              <Button
                onClick={handleSendMessage}
                className="flex-1 bg-gradient-to-r from-orange-500 to-pink-500 hover:from-orange-600 hover:to-pink-600 text-white font-poppins"
              >
                <Send className="w-4 h-4 mr-2" />
                Send Smile
              </Button>
            </div>
          </div>
        </div>
      )}

      {/* Delete Confirmation Dialog */}
      {confirmDeleteId && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" onClick={() => setConfirmDeleteId(null)}>
          <div className="bg-white rounded-3xl p-8 max-w-md w-full shadow-2xl relative" onClick={(e) => e.stopPropagation()}>
            <button
              onClick={() => setConfirmDeleteId(null)}
              className="absolute top-4 right-4 text-gray-400 hover:text-gray-600"
            >
              <X className="w-6 h-6" />
            </button>
            <h3 className="text-2xl font-bold text-gray-900 mb-2 font-poppins">
              Delete this message?
            </h3>
            <p className="text-gray-600 mb-2 font-lora">
              This will remove the message from your dashboard.
            </p>
            <p className="text-sm text-orange-600 mb-6 font-lora">
              ⚠️ The email was already sent, but the link will no longer work when they try to view it.
            </p>
            <div className="flex gap-3">
              <Button
                onClick={() => setConfirmDeleteId(null)}
                variant="outline"
                className="flex-1 font-poppins"
              >
                Cancel
              </Button>
              <Button
                onClick={() => handleDelete(confirmDeleteId)}
                className="flex-1 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white font-poppins"
              >
                <Trash2 className="w-4 h-4 mr-2" />
                Delete
              </Button>
            </div>
          </div>
        </div>
      )}

      {/* Global Progress Modal */}
      <GlobalProgressModal
        isOpen={showProgress}
        onClose={() => setShowProgress(false)}
      />

      {/* Company Leaderboard Modal */}
      <CompanyLeaderboardModal
        isOpen={showLeaderboard}
        onClose={() => setShowLeaderboard(false)}
      />
    </div>
  );
}