import type { ChartData, SalesChartData, CategoryPerformance } from '@/types/dashboard';

// Generate sample sales chart data
export const generateSalesChartData = (period: 'daily' | 'weekly' | 'monthly' | 'yearly' = 'monthly'): SalesChartData => {
  const now = new Date();
  const labels: string[] = [];
  const salesData: number[] = [];
  const profitData: number[] = [];

  let periods = 12;
  let dateFormat: Intl.DateTimeFormatOptions = { month: 'short' };

  switch (period) {
    case 'daily':
      periods = 30;
      dateFormat = { day: 'numeric', month: 'short' };
      break;
    case 'weekly':
      periods = 12;
      dateFormat = { day: 'numeric', month: 'short' };
      break;
    case 'yearly':
      periods = 5;
      dateFormat = { year: 'numeric' };
      break;
  }

  for (let i = periods - 1; i >= 0; i--) {
    const date = new Date(now);
    
    switch (period) {
      case 'daily':
        date.setDate(date.getDate() - i);
        break;
      case 'weekly':
        date.setDate(date.getDate() - (i * 7));
        break;
      case 'monthly':
        date.setMonth(date.getMonth() - i);
        break;
      case 'yearly':
        date.setFullYear(date.getFullYear() - i);
        break;
    }

    labels.push(date.toLocaleDateString('en-US', dateFormat));
    
    // Generate realistic sales data with some randomness
    const baseSales = 15000 + Math.random() * 10000;
    const seasonalMultiplier = 1 + 0.3 * Math.sin((i / periods) * Math.PI * 2);
    const sales = Math.round(baseSales * seasonalMultiplier);
    const profit = Math.round(sales * (0.25 + Math.random() * 0.15));
    
    salesData.push(sales);
    profitData.push(profit);
  }

  return {
    labels,
    datasets: [
      {
        label: 'Sales',
        data: salesData,
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        borderColor: 'rgba(59, 130, 246, 1)',
        borderWidth: 2
      },
      {
        label: 'Profit',
        data: profitData,
        backgroundColor: 'rgba(16, 185, 129, 0.1)',
        borderColor: 'rgba(16, 185, 129, 1)',
        borderWidth: 2
      }
    ],
    period,
    currency: 'USD'
  };
};

// Generate category performance data
export const generateCategoryPerformance = (): CategoryPerformance[] => {
  const categories = [
    'Gold Rings',
    'Gold Necklaces',
    'Gold Bracelets',
    'Gold Earrings',
    'Silver Jewelry',
    'Precious Stones'
  ];

  return categories.map(category => ({
    category,
    sales: Math.round(5000 + Math.random() * 15000),
    profit: Math.round(1000 + Math.random() * 5000),
    margin: Math.round(20 + Math.random() * 15),
    change: Math.round(-10 + Math.random() * 20)
  }));
};

// Generate doughnut chart data for category distribution
export const generateCategoryDistributionData = (): ChartData => {
  const categories = ['Gold', 'Silver', 'Platinum', 'Gems', 'Others'];
  const data = categories.map(() => Math.round(10 + Math.random() * 40));
  
  const colors = [
    '#FFD700', // Gold
    '#C0C0C0', // Silver
    '#E5E4E2', // Platinum
    '#FF6B6B', // Gems
    '#4ECDC4'  // Others
  ];

  return {
    labels: categories,
    datasets: [
      {
        label: 'Distribution',
        data,
        backgroundColor: colors,
        borderColor: colors.map(color => color),
        borderWidth: 2
      }
    ]
  };
};

// Generate profit/loss chart data
export const generateProfitLossData = (): ChartData => {
  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  const revenue = months.map(() => Math.round(20000 + Math.random() * 15000));
  const expenses = months.map(() => Math.round(12000 + Math.random() * 8000));
  const profit = revenue.map((rev, index) => rev - expenses[index]);

  return {
    labels: months,
    datasets: [
      {
        label: 'Revenue',
        data: revenue,
        backgroundColor: 'rgba(34, 197, 94, 0.8)',
        borderColor: 'rgba(34, 197, 94, 1)',
        borderWidth: 1
      },
      {
        label: 'Expenses',
        data: expenses,
        backgroundColor: 'rgba(239, 68, 68, 0.8)',
        borderColor: 'rgba(239, 68, 68, 1)',
        borderWidth: 1
      },
      {
        label: 'Profit',
        data: profit,
        backgroundColor: 'rgba(59, 130, 246, 0.8)',
        borderColor: 'rgba(59, 130, 246, 1)',
        borderWidth: 1
      }
    ]
  };
};

// Generate cash flow data
export const generateCashFlowData = (): ChartData => {
  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
  const cashIn = months.map(() => Math.round(25000 + Math.random() * 10000));
  const cashOut = months.map(() => Math.round(18000 + Math.random() * 8000));

  return {
    labels: months,
    datasets: [
      {
        label: 'Cash In',
        data: cashIn,
        backgroundColor: 'rgba(34, 197, 94, 0.6)',
        borderColor: 'rgba(34, 197, 94, 1)',
        borderWidth: 2
      },
      {
        label: 'Cash Out',
        data: cashOut,
        backgroundColor: 'rgba(239, 68, 68, 0.6)',
        borderColor: 'rgba(239, 68, 68, 1)',
        borderWidth: 2
      }
    ]
  };
};

// Generate sales funnel data
export const generateSalesFunnelData = (): ChartData => {
  const stages = ['Leads', 'Qualified', 'Proposal', 'Negotiation', 'Closed'];
  const data = [1000, 750, 500, 300, 200]; // Funnel progression

  return {
    labels: stages,
    datasets: [
      {
        label: 'Sales Funnel',
        data,
        backgroundColor: [
          'rgba(59, 130, 246, 0.8)',
          'rgba(16, 185, 129, 0.8)',
          'rgba(245, 158, 11, 0.8)',
          'rgba(239, 68, 68, 0.8)',
          'rgba(139, 92, 246, 0.8)'
        ],
        borderColor: [
          'rgba(59, 130, 246, 1)',
          'rgba(16, 185, 129, 1)',
          'rgba(245, 158, 11, 1)',
          'rgba(239, 68, 68, 1)',
          'rgba(139, 92, 246, 1)'
        ],
        borderWidth: 2
      }
    ]
  };
};

// Generate customer acquisition data
export const generateCustomerAcquisitionData = (): ChartData => {
  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
  const newCustomers = months.map(() => Math.round(15 + Math.random() * 25));
  const returningCustomers = months.map(() => Math.round(30 + Math.random() * 20));

  return {
    labels: months,
    datasets: [
      {
        label: 'New Customers',
        data: newCustomers,
        backgroundColor: 'rgba(59, 130, 246, 0.8)',
        borderColor: 'rgba(59, 130, 246, 1)',
        borderWidth: 1
      },
      {
        label: 'Returning Customers',
        data: returningCustomers,
        backgroundColor: 'rgba(16, 185, 129, 0.8)',
        borderColor: 'rgba(16, 185, 129, 1)',
        borderWidth: 1
      }
    ]
  };
};