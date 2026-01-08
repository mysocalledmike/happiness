import { useState } from 'react';
import { Check, X, Mail, Calendar, Clock, MoreVertical, Copy, RefreshCw, Trash2, Users, Zap } from 'lucide-react';
import { Logo } from './Logo';
import { Button } from './ui/button';

interface User {
  id: string;
  email: string;
  confirmed: boolean;
  messagesSent: number;
  createdAt: string;
  lastActivity: string;
  dashboardSlug: string;
}

interface AdminDashboardProps {
  onNavigateHome: () => void;
}

type ConfirmDialogType = 'reminder' | 'reset' | 'delete';

interface ConfirmDialog {
  type: ConfirmDialogType;
  userId: string;
  userEmail: string;
}

// Generate random 8-character slug
const generateSlug = () => {
  return Math.random().toString(36).substring(2, 10);
};

// Mock data
const mockUsers: User[] = [
  {
    id: '1',
    email: 'betterflow@test.com',
    confirmed: true,
    messagesSent: 12,
    createdAt: '2024-01-15',
    lastActivity: '2 hours ago',
    dashboardSlug: 'akjd82js',
  },
  {
    id: '2',
    email: 'sarah@example.com',
    confirmed: true,
    messagesSent: 8,
    createdAt: '2024-02-20',
    lastActivity: '1 day ago',
    dashboardSlug: 'mn3kd92f',
  },
  {
    id: '3',
    email: 'mike@example.com',
    confirmed: false,
    messagesSent: 0,
    createdAt: '2024-03-10',
    lastActivity: '3 days ago',
    dashboardSlug: 'pq7rt4ws',
  },
  {
    id: '4',
    email: 'john@john.com',
    confirmed: false,
    messagesSent: 0,
    createdAt: '2024-03-12',
    lastActivity: '5 days ago',
    dashboardSlug: 'xy9bn2cd',
  },
  {
    id: '5',
    email: 'neil@neil.com',
    confirmed: true,
    messagesSent: 24,
    createdAt: '2024-01-05',
    lastActivity: '30 minutes ago',
    dashboardSlug: 'lm6op1vx',
  },
  {
    id: '6',
    email: 'finaltest@example.com',
    confirmed: false,
    messagesSent: 0,
    createdAt: '2024-03-13',
    lastActivity: '1 week ago',
    dashboardSlug: 'gh5ij8kl',
  },
  {
    id: '7',
    email: 'test@example.com',
    confirmed: true,
    messagesSent: 5,
    createdAt: '2024-02-28',
    lastActivity: '4 hours ago',
    dashboardSlug: 'ab3cd7ef',
  },
];

export function AdminDashboard({ onNavigateHome }: AdminDashboardProps) {
  const [users, setUsers] = useState<User[]>(mockUsers);
  const [emailFilter, setEmailFilter] = useState<'all' | 'confirmed' | 'unconfirmed'>('all');
  const [minMessages, setMinMessages] = useState<number>(0);
  const [openMenuId, setOpenMenuId] = useState<string | null>(null);
  const [confirmDialog, setConfirmDialog] = useState<ConfirmDialog | null>(null);
  const [copiedSlug, setCopiedSlug] = useState<string | null>(null);

  // Filter users based on selected filters
  const filteredUsers = users.filter((user) => {
    const emailMatch =
      emailFilter === 'all' ||
      (emailFilter === 'confirmed' && user.confirmed) ||
      (emailFilter === 'unconfirmed' && !user.confirmed);
    
    const messagesMatch = user.messagesSent >= minMessages;

    return emailMatch && messagesMatch;
  });

  const totalUsers = users.length;
  const confirmedUsers = users.filter((u) => u.confirmed).length;
  const activeUsers = users.filter((u) => u.confirmed && u.messagesSent > 0).length;
  const totalMessages = users.reduce((sum, u) => sum + u.messagesSent, 0);

  const handleSendReminder = (userId: string) => {
    const user = users.find((u) => u.id === userId);
    if (!user) return;
    setConfirmDialog({ type: 'reminder', userId, userEmail: user.email });
    setOpenMenuId(null);
  };

  const handleResetUrl = (userId: string) => {
    const user = users.find((u) => u.id === userId);
    if (!user) return;
    setConfirmDialog({ type: 'reset', userId, userEmail: user.email });
    setOpenMenuId(null);
  };

  const handleDeleteUser = (userId: string) => {
    const user = users.find((u) => u.id === userId);
    if (!user) return;
    setConfirmDialog({ type: 'delete', userId, userEmail: user.email });
    setOpenMenuId(null);
  };

  const confirmAction = () => {
    if (!confirmDialog) return;

    if (confirmDialog.type === 'reminder') {
      // Mock: Send reminder email
      console.log('Sending reminder to:', confirmDialog.userEmail);
    } else if (confirmDialog.type === 'reset') {
      // Reset dashboard URL
      setUsers(users.map((u) =>
        u.id === confirmDialog.userId
          ? { ...u, dashboardSlug: generateSlug() }
          : u
      ));
    } else if (confirmDialog.type === 'delete') {
      // Delete user
      setUsers(users.filter((u) => u.id !== confirmDialog.userId));
    }

    setConfirmDialog(null);
  };

  const handleCopySlug = (slug: string) => {
    navigator.clipboard.writeText(`/${slug}`);
    setCopiedSlug(slug);
    setTimeout(() => setCopiedSlug(null), 2000);
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-orange-50 via-pink-50 to-orange-50">
      {/* Sticky Header */}
      <div className="sticky top-0 z-50 bg-gradient-to-r from-orange-500 via-pink-500 to-orange-500 shadow-lg">
        <div className="max-w-7xl mx-auto px-6 py-4">
          <div className="flex items-center justify-between gap-8">
            {/* Logo + Title */}
            <div className="flex items-center gap-4">
              <Logo />
              <h1 className="text-2xl font-bold text-white font-poppins">Admin Dashboard</h1>
            </div>

            {/* Stats */}
            <div className="flex items-center gap-6">
              <div className="text-center">
                <div className="flex items-center gap-2">
                  <Users className="w-4 h-4 text-white/80" />
                  <p className="text-xs text-white/80 font-lora">Total</p>
                </div>
                <p className="text-2xl font-bold text-white font-poppins">{totalUsers}</p>
              </div>

              <div className="h-8 w-px bg-white/30" />

              <div className="text-center">
                <div className="flex items-center gap-2">
                  <Check className="w-4 h-4 text-white/80" />
                  <p className="text-xs text-white/80 font-lora">Confirmed</p>
                </div>
                <p className="text-2xl font-bold text-white font-poppins">{confirmedUsers}</p>
              </div>

              <div className="h-8 w-px bg-white/30" />

              <div className="text-center">
                <div className="flex items-center gap-2">
                  <Zap className="w-4 h-4 text-white/80" />
                  <p className="text-xs text-white/80 font-lora">Active</p>
                </div>
                <p className="text-2xl font-bold text-white font-poppins">{activeUsers}</p>
              </div>

              <div className="h-8 w-px bg-white/30" />

              <div className="text-center">
                <div className="flex items-center gap-2">
                  <span className="text-white/80">✨</span>
                  <p className="text-xs text-white/80 font-lora">Smiles</p>
                </div>
                <p className="text-2xl font-bold text-white font-poppins">
                  {totalMessages}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div className="max-w-7xl mx-auto px-6 py-12">
        {/* Filters */}
        <div className="bg-white rounded-3xl p-6 shadow-lg mb-6">
          <h2 className="text-xl font-bold text-gray-900 mb-4 font-poppins">Filters</h2>
          
          <div className="flex flex-col md:flex-row gap-6">
            {/* Email Status Filter */}
            <div className="flex-1">
              <label className="text-sm text-gray-600 mb-2 block font-lora">Email Status</label>
              <div className="flex gap-2">
                <Button
                  onClick={() => setEmailFilter('all')}
                  className={`flex-1 rounded-full ${
                    emailFilter === 'all'
                      ? 'bg-gradient-to-r from-orange-500 to-pink-500 text-white'
                      : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                  }`}
                >
                  All
                </Button>
                <Button
                  onClick={() => setEmailFilter('confirmed')}
                  className={`flex-1 rounded-full ${
                    emailFilter === 'confirmed'
                      ? 'bg-gradient-to-r from-orange-500 to-pink-500 text-white'
                      : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                  }`}
                >
                  Confirmed
                </Button>
                <Button
                  onClick={() => setEmailFilter('unconfirmed')}
                  className={`flex-1 rounded-full ${
                    emailFilter === 'unconfirmed'
                      ? 'bg-gradient-to-r from-orange-500 to-pink-500 text-white'
                      : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                  }`}
                >
                  Unconfirmed
                </Button>
              </div>
            </div>

            {/* Messages Sent Filter */}
            <div className="flex-1">
              <label className="text-sm text-gray-600 mb-2 block font-lora">Minimum Smiles Sent</label>
              <select
                value={minMessages}
                onChange={(e) => setMinMessages(Number(e.target.value))}
                className="w-full px-4 py-2 rounded-full border border-gray-200 focus:border-orange-400 focus:ring-2 focus:ring-orange-200 outline-none font-lora"
              >
                <option value={0}>0+</option>
                <option value={1}>1+</option>
                <option value={3}>3+</option>
                <option value={5}>5+</option>
                <option value={10}>10+</option>
              </select>
            </div>
          </div>

          {/* Results Count */}
          <div className="mt-4 text-sm text-gray-600 font-lora">
            Showing <span className="font-bold text-orange-600">{filteredUsers.length}</span> of{' '}
            <span className="font-bold">{totalUsers}</span> users
          </div>
        </div>

        {/* Users Table */}
        <div className="bg-white rounded-3xl shadow-lg overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gradient-to-r from-orange-50 to-pink-50">
                <tr>
                  <th className="text-left px-6 py-4 text-sm font-bold text-gray-700 font-poppins">
                    Email
                  </th>
                  <th className="text-center px-6 py-4 text-sm font-bold text-gray-700 font-poppins">
                    Confirmed
                  </th>
                  <th className="text-center px-6 py-4 text-sm font-bold text-gray-700 font-poppins">
                    Smiles Sent
                  </th>
                  <th className="text-left px-6 py-4 text-sm font-bold text-gray-700 font-poppins">
                    Dashboard URL
                  </th>
                  <th className="text-left px-6 py-4 text-sm font-bold text-gray-700 font-poppins">
                    Created At
                  </th>
                  <th className="text-left px-6 py-4 text-sm font-bold text-gray-700 font-poppins">
                    Last Activity
                  </th>
                  <th className="text-center px-6 py-4 text-sm font-bold text-gray-700 font-poppins">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {filteredUsers.length === 0 ? (
                  <tr>
                    <td colSpan={7} className="px-6 py-12 text-center text-gray-500 font-lora">
                      No users match the current filters
                    </td>
                  </tr>
                ) : (
                  filteredUsers.map((user) => (
                    <tr
                      key={user.id}
                      className="hover:bg-orange-50/50 transition-colors"
                    >
                      <td className="px-6 py-4">
                        <div className="flex items-center gap-2">
                          <Mail className="w-4 h-4 text-gray-400" />
                          <span className="font-lora text-gray-900">{user.email}</span>
                        </div>
                      </td>
                      <td className="px-6 py-4 text-center">
                        {user.confirmed ? (
                          <div className="inline-flex items-center justify-center w-8 h-8 rounded-full bg-green-100">
                            <Check className="w-5 h-5 text-green-600" />
                          </div>
                        ) : (
                          <div className="inline-flex items-center justify-center w-8 h-8 rounded-full bg-red-100">
                            <X className="w-5 h-5 text-red-600" />
                          </div>
                        )}
                      </td>
                      <td className="px-6 py-4 text-center">
                        <span className="inline-flex items-center justify-center min-w-[2rem] px-3 py-1 rounded-full bg-gradient-to-r from-orange-100 to-pink-100 text-orange-900 font-bold text-sm">
                          {user.messagesSent}
                        </span>
                      </td>
                      <td className="px-6 py-4">
                        <button
                          onClick={() => handleCopySlug(user.dashboardSlug)}
                          className="flex items-center gap-2 text-gray-600 hover:text-orange-600 transition-colors group"
                        >
                          <span className="font-mono text-sm bg-gray-100 px-3 py-1 rounded-full">
                            /{user.dashboardSlug}
                          </span>
                          {copiedSlug === user.dashboardSlug ? (
                            <Check className="w-4 h-4 text-green-500" />
                          ) : (
                            <Copy className="w-4 h-4 opacity-0 group-hover:opacity-100 transition-opacity" />
                          )}
                        </button>
                      </td>
                      <td className="px-6 py-4">
                        <div className="flex items-center gap-2 text-gray-600 font-lora text-sm">
                          <Calendar className="w-4 h-4 text-gray-400" />
                          {new Date(user.createdAt).toLocaleDateString('en-US', {
                            month: 'short',
                            day: 'numeric',
                            year: 'numeric',
                          })}
                        </div>
                      </td>
                      <td className="px-6 py-4">
                        <div className="flex items-center gap-2 text-gray-600 font-lora text-sm">
                          <Clock className="w-4 h-4 text-gray-400" />
                          {user.lastActivity}
                        </div>
                      </td>
                      <td className="px-6 py-4 text-center">
                        <div className="relative">
                          <Button
                            onClick={() => setOpenMenuId(openMenuId === user.id ? null : user.id)}
                            variant="ghost"
                            size="sm"
                            className="text-gray-600 hover:text-gray-900 hover:bg-gray-100"
                          >
                            <MoreVertical className="w-5 h-5" />
                          </Button>

                          {/* Dropdown Menu */}
                          {openMenuId === user.id && (
                            <>
                              {/* Backdrop */}
                              <div
                                className="fixed inset-0 z-10"
                                onClick={() => setOpenMenuId(null)}
                              />
                              {/* Menu */}
                              <div className="absolute right-0 top-full mt-1 w-56 bg-white rounded-2xl shadow-xl border border-gray-200 py-2 z-20">
                                <button
                                  onClick={() => handleSendReminder(user.id)}
                                  className="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-orange-50 flex items-center gap-3 font-lora"
                                >
                                  <Mail className="w-4 h-4 text-orange-600" />
                                  Send reminder email
                                </button>
                                <button
                                  onClick={() => handleResetUrl(user.id)}
                                  className="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-orange-50 flex items-center gap-3 font-lora"
                                >
                                  <RefreshCw className="w-4 h-4 text-orange-600" />
                                  Reset dashboard URL
                                </button>
                                <div className="border-t border-gray-200 my-1" />
                                <button
                                  onClick={() => handleDeleteUser(user.id)}
                                  className="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 flex items-center gap-3 font-lora"
                                >
                                  <Trash2 className="w-4 h-4" />
                                  Delete user
                                </button>
                              </div>
                            </>
                          )}
                        </div>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {/* Confirmation Dialog */}
      {confirmDialog && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-6">
          <div className="bg-white rounded-3xl p-8 max-w-md w-full shadow-2xl">
            <h3 className="text-2xl font-bold text-gray-900 mb-3 font-poppins">
              {confirmDialog.type === 'reminder' && 'Send Reminder Email?'}
              {confirmDialog.type === 'reset' && 'Reset Dashboard URL?'}
              {confirmDialog.type === 'delete' && 'Delete User?'}
            </h3>
            <p className="text-gray-600 mb-6 font-lora">
              {confirmDialog.type === 'reminder' && (
                <>
                  Send a reminder email to <strong>{confirmDialog.userEmail}</strong> to confirm
                  their account?
                </>
              )}
              {confirmDialog.type === 'reset' && (
                <>
                  This will generate a new dashboard URL for <strong>{confirmDialog.userEmail}</strong>.
                  The old URL will no longer work.
                </>
              )}
              {confirmDialog.type === 'delete' && (
                <>
                  Are you sure you want to permanently delete <strong>{confirmDialog.userEmail}</strong>?
                  This action cannot be undone.
                </>
              )}
            </p>
            <div className="flex gap-3">
              <Button
                onClick={() => setConfirmDialog(null)}
                className="flex-1 rounded-full bg-gray-100 text-gray-700 hover:bg-gray-200"
              >
                Cancel
              </Button>
              <Button
                onClick={confirmAction}
                className={`flex-1 rounded-full ${
                  confirmDialog.type === 'delete'
                    ? 'bg-red-500 hover:bg-red-600 text-white'
                    : 'bg-gradient-to-r from-orange-500 to-pink-500 hover:from-orange-600 hover:to-pink-600 text-white'
                }`}
              >
                {confirmDialog.type === 'reminder' && 'Send Email'}
                {confirmDialog.type === 'reset' && 'Reset URL'}
                {confirmDialog.type === 'delete' && 'Delete User'}
              </Button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}